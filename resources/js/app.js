import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

document.addEventListener('alpine:init', () => {
    Alpine.data('bardEditor', (content, model) => {
        // Kept as a plain closure variable, NOT a property on the returned
        // object below — Alpine wraps every property of an x-data object in
        // a reactive Proxy, and ProseMirror's transaction system relies on
        // strict `===` identity checks internally (e.g. `tr.before ===
        // state.doc`). A reactive-proxied Editor instance fails those
        // checks and throws "Applying a mismatched transaction" on every
        // command. This is the same reason Tiptap's own Vue integration
        // wraps its editor in Vue's markRaw().
        let editor = null;

        return {
            tick: 0,

            init() {
                editor = new Editor({
                    element: this.$refs.editor,
                    // Link and Underline are already bundled by StarterKit
                    // in Tiptap v3 — importing them separately registered
                    // each extension twice, which also corrupts transactions.
                    extensions: [StarterKit.configure({ link: { openOnClick: false } })],
                    content: content || '',
                    onUpdate: ({ editor }) => {
                        this.$wire.set(model, editor.isEmpty ? '' : editor.getHTML());
                    },
                    onTransaction: () => {
                        this.tick++;
                    },
                });
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

            destroy() {
                editor?.destroy();
            },
        };
    });
});
