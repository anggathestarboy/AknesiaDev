import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

document.addEventListener('alpine:init', () => {
    Alpine.data('bardEditor', (content, model, container) => {
        // Kept as a plain closure variable, NOT a property on the returned
        // object below — Alpine wraps every property of an x-data object in
        // a reactive Proxy, and ProseMirror's transaction system relies on
        // strict `===` identity checks internally (e.g. `tr.before ===
        // state.doc`). A reactive-proxied Editor instance fails those
        // checks and throws "Applying a mismatched transaction" on every
        // command. This is the same reason Tiptap's own Vue integration
        // wraps its editor in Vue's markRaw().
        let editor = null;

        // Position (in the ProseMirror doc) of the image node being replaced
        // via the toolbar menu — set right before opening the asset picker,
        // consumed once "bard-image-selected" reports back with mode "replace".
        let replacingPos = null;

        // The shared media::asset-picker (see entry-form.blade.php) reports
        // its selection back via a global "assets-selected" browser event
        // keyed by "path" — since a bard field has no "assets" data
        // property to write into, we open it with a sentinel path and the
        // Livewire component (EntryForm/TermForm/GlobalSetForm) forwards
        // the resolved image URLs to us via "bard-image-selected" instead.
        const onImageSelected = (event) => {
            if (event.detail.prefix !== model) {
                return;
            }

            if (event.detail.mode === 'replace') {
                if (replacingPos !== null) {
                    editor.chain().focus().setNodeSelection(replacingPos).updateAttributes('image', { src: event.detail.urls[0] }).run();
                    replacingPos = null;
                }

                return;
            }

            event.detail.urls.forEach((src) => {
                editor.chain().focus().setImage({ src }).run();
            });
        };

        return {
            tick: 0,
            imageMenu: null,

            init() {
                editor = new Editor({
                    element: this.$refs.editor,
                    // Link and Underline are already bundled by StarterKit
                    // in Tiptap v3 — importing them separately registered
                    // each extension twice, which also corrupts transactions.
                    extensions: [StarterKit.configure({ link: { openOnClick: false } }), Image],
                    content: content || '',
                    onUpdate: ({ editor }) => {
                        this.imageMenu = null;
                        this.$wire.set(model, editor.isEmpty ? '' : editor.getHTML());
                    },
                    onTransaction: () => {
                        this.tick++;
                    },
                });

                window.addEventListener('bard-image-selected', onImageSelected);
            },

            onEditorClick(event) {
                if (event.target.tagName !== 'IMG') {
                    this.imageMenu = null;

                    return;
                }

                const pos = editor.view.posAtDOM(event.target, 0);
                const imageRect = event.target.getBoundingClientRect();
                const containerRect = this.$refs.editor.getBoundingClientRect();

                editor.chain().setNodeSelection(pos).run();

                this.imageMenu = {
                    pos,
                    top: imageRect.bottom - containerRect.top + 4,
                    left: imageRect.left - containerRect.left,
                };
            },

            replaceImage() {
                if (this.imageMenu === null) {
                    return;
                }

                replacingPos = this.imageMenu.pos;
                this.imageMenu = null;

                this.$dispatch('open-asset-picker', {
                    path: ['__bard__', model, 'replace'],
                    container,
                    selected: [],
                });
            },

            deleteImage() {
                if (this.imageMenu === null) {
                    return;
                }

                const { pos } = this.imageMenu;
                editor.chain().focus().deleteRange({ from: pos, to: pos + 1 }).run();
                this.imageMenu = null;
            },

            isActive(name, attrs = {}) {
                this.tick;

                return editor?.isActive(name, attrs) ?? false;
            },

            run(chainMethod, ...args) {
                editor.chain().focus()[chainMethod](...args).run();
            },

            setLink() {
                const url = window.prompt('URL');

                if (url === null) {
                    return;
                }

                if (url === '') {
                    editor.chain().focus().unsetLink().run();

                    return;
                }

                editor.chain().focus().setLink({ href: url }).run();
            },

            insertImage() {
                this.$dispatch('open-asset-picker', {
                    path: ['__bard__', model],
                    container,
                    selected: [],
                });
            },

            destroy() {
                window.removeEventListener('bard-image-selected', onImageSelected);
                editor?.destroy();
            },
        };
    });
});
