<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">

    <section class="relative overflow-hidden bg-linear-to-b from-red-100/60 to-red-50 py-12 lg:py-16">
        <div class="pointer-events-none absolute -left-40 top-1/3 size-150 rounded-full bg-red-200/30 blur-2xl"></div>
        <div class="pointer-events-none absolute -right-32 -top-20 size-130 rounded-full bg-white/40 blur-2xl"></div>

        <div class="relative container mx-auto px-6 xl:px-0">
            <nav aria-label="Breadcrumb" class="flex flex-nowrap items-center gap-3 text-sm lg:flex-wrap">
                <a href="#"
                    class="shrink-0 whitespace-nowrap text-slate-600 hover:text-red-600">{{ $site?->name == 'Indonesia' ? 'Beranda' : 'Home' }}</a>
                <svg class="size-4 shrink-0 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true">
                    <path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <a href="#"
                    class="shrink-0 whitespace-nowrap text-slate-600 hover:text-red-600">{{ $site?->name == 'Indonesia' ? 'Berita' : 'News' }}</a>
                <svg class="size-4 shrink-0 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true">
                    <path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span aria-current="page"
                    class="min-w-0 line-clamp-1 font-medium text-red-600 lg:line-clamp-none">{{ $data->title }}</span>
            </nav>

            <div class="mt-12 grid items-center gap-10 lg:mt-16 lg:grid-cols-2 lg:gap-16">
                <div>
                    <h1 class="text-3xl font-bold leading-[1.4] tracking-tight text-slate-900 sm:text-4xl">
                        {{ $data->title }}
                    </h1>

                    <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-4">
                        <div>
                            <dt class="text-sm text-slate-500">{{ $site?->name == 'Indonesia' ? 'Penulis' : 'Author' }}
                            </dt>
                            <dd class="mt-2 font-medium uppercase text-slate-800">{{ $data->author?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500">
                                {{ $site?->name == 'Indonesia' ? 'Tanggal Publikasi' : 'Date' }}</dt>
                            <dd class="mt-2 font-medium uppercase text-slate-800">{{ $data->date->format('d/m/Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500">
                                {{ $site?->name == 'Indonesia' ? 'Kategori' : 'Category' }}</dt>
                            <dd class="mt-2 font-medium uppercase text-slate-800">
                                @foreach ($data->news_taxonomy ?? [] as $category)
                                    <span> {{ $category->taxonomy_name }} </span>
                                @endforeach
                            </dd>
                        </div>
                    </dl>
                </div>
                <figure>
                    <img src="{{ $data->news_image->url }}" alt="Aksnesia"
                        class="aspect-16/10 w-full rounded-2xl object-cover shadow-sm" />
                </figure>
            </div>
        </div>
    </section>

   <section class="bg-white py-10 lg:py-26">
    <div class="container mx-auto px-6 xl:px-0">
        <div
            class="grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16 xl:grid-cols-[minmax(0,1fr)_360px]">
            <article id="article-content"
                class="order-2 min-w-0 text-base leading-7 text-slate-700 lg:order-none
                [&>:first-child]:mt-0
                [&_h2]:mt-10 [&_h2]:mb-4 [&_h2]:scroll-mt-28 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:leading-tight [&_h2]:text-slate-900
                [&_h3]:mt-8 [&_h3]:mb-3 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:text-slate-900
                [&_p]:mb-4 [&_strong]:font-semibold [&_strong]:text-slate-900
                [&_a]:text-red-600 [&_a]:underline hover:[&_a]:text-red-700
                [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:mb-1
                [&_blockquote]:my-6 [&_blockquote]:border-l-4 [&_blockquote]:border-red-500 [&_blockquote]:pl-4 [&_blockquote]:italic
                [&_img]:my-6 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-xl
                [&_table]:my-6 [&_table]:w-full [&_td]:border [&_td]:border-slate-200 [&_td]:p-2 [&_th]:border [&_th]:border-slate-200 [&_th]:p-2">
                {!! $data->news_desc !!}
            </article>

            <aside class="contents lg:block">
                <div class="contents lg:sticky lg:top-12 lg:block lg:space-y-8">
                    <div id="toc-wrapper" class="order-1 hidden lg:order-none">
                        <button type="button" id="toc-toggle" aria-expanded="false" aria-controls="toc-body"
                            class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 lg:hidden">
                            <span>{{ $site?->name == 'Indonesia' ? 'Daftar Isi' : 'Table of Contents' }}</span>
                            <svg id="toc-chevron" class="size-4 transition-transform" viewBox="0 0 20 20"
                                fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="m5 8 5 5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <p class="hidden text-sm text-slate-500 lg:block">
                            {{ $site?->name == 'Indonesia' ? 'Daftar Isi' : 'Table of Contents' }}</p>
                        <div id="toc-body" class="mt-3 hidden lg:block">
                            <ul id="toc-list"
                                class="max-h-[50vh] space-y-1 overflow-y-auto border-l border-slate-200"></ul>
                        </div>
                    </div>

                    <div class="order-3 border-t border-slate-200 pt-6 lg:order-none lg:border-t-0 lg:pt-0">
                        <p class="text-sm text-slate-500">
                            {{ $site?->name == 'Indonesia' ? 'Bagikan Berita' : 'Share News' }}</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <a href="https://wa.me/?text={{ urlencode($data->title . ' ' . url()->current()) }}"
                                target="_blank" rel="noopener" aria-label="WhatsApp"
                                class="grid size-9 place-items-center rounded-full bg-black text-white transition hover:bg-red-600">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($data->title) }}&url={{ urlencode(url()->current()) }}"
                                target="_blank" rel="noopener" aria-label="X"
                                class="grid size-9 place-items-center rounded-full bg-black text-white transition hover:bg-red-600">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                                target="_blank" rel="noopener" aria-label="Facebook"
                                class="grid size-9 place-items-center rounded-full bg-black text-white transition hover:bg-red-600">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}"
                                target="_blank" rel="noopener" aria-label="LinkedIn"
                                class="grid size-9 place-items-center rounded-full bg-black text-white transition hover:bg-red-600">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                </svg>
                            </a>
                            <button type="button" id="copy-link" data-url="{{ url()->current() }}"
                                aria-label="Salin tautan"
                                class="grid size-9 place-items-center rounded-full bg-black text-white transition hover:bg-red-600">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                </svg>
                            </button>
                        </div>
                        <p id="copy-info" class="mt-2 hidden text-xs text-red-600">
                            {{ $site?->name == 'Indonesia' ? 'Tautan disalin' : 'Link copied' }}</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const article = document.getElementById('article-content');
        const wrapper = document.getElementById('toc-wrapper');
        const list = document.getElementById('toc-list');
        const toggle = document.getElementById('toc-toggle');
        const body = document.getElementById('toc-body');
        const chevron = document.getElementById('toc-chevron');
        const desktop = window.matchMedia('(min-width: 1024px)');
        const headings = article.querySelectorAll('h2');

        let node = article.parentElement;
        while (node && node !== document.documentElement) {
            const s = getComputedStyle(node);
            const blocks = [s.overflowX, s.overflowY].some((v) => ['hidden', 'auto', 'scroll'].includes(v));
            if (blocks && node.scrollHeight <= node.clientHeight + 1) {
                node.style.setProperty('overflow', 'clip');
            }
            node = node.parentElement;
        }

        const setOpen = (open) => {
            toggle.setAttribute('aria-expanded', String(open));
            body.classList.toggle('hidden', !open);
            chevron.classList.toggle('rotate-180', open);
        };

        toggle.addEventListener('click', () => {
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        if (headings.length) {
            const used = new Set();
            const slugify = (text) => text.toLowerCase().trim().replace(/[^\p{L}\p{N}]+/gu, '-').replace(
                /^-+|-+$/g, '') || 'bagian';
            const links = [];

            headings.forEach((h, i) => {
                let id = h.id || slugify(h.textContent);
                while (used.has(id)) id = `${id}-${i}`;
                used.add(id);
                h.id = id;

                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = `#${id}`;
                a.textContent = h.textContent;
                a.className =
                    '-ml-px block border-l-2 border-transparent py-1.5 pl-4 text-sm text-slate-600 transition hover:text-red-600';
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!desktop.matches) setOpen(false);
                    h.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    history.replaceState(null, '', `#${id}`);
                });
                li.appendChild(a);
                list.appendChild(li);
                links.push({
                    h,
                    a
                });
            });

            wrapper.classList.remove('hidden');

            const setActive = (target) => {
                links.forEach(({
                    a
                }) => {
                    const active = a === target;
                    a.classList.toggle('border-red-600', active);
                    a.classList.toggle('font-semibold', active);
                    a.classList.toggle('text-red-600', active);
                    a.classList.toggle('border-transparent', !active);
                    a.classList.toggle('text-slate-600', !active);
                });
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setActive(links.find((l) => l.h === entry.target).a);
                    }
                });
            }, {
                rootMargin: '-100px 0px -70% 0px'
            });

            headings.forEach((h) => observer.observe(h));
        }

        const copyBtn = document.getElementById('copy-link');
        const info = document.getElementById('copy-info');
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(copyBtn.dataset.url);
                info.classList.remove('hidden');
                setTimeout(() => info.classList.add('hidden'), 2000);
            } catch (e) {}
        });
    });
</script>

</x-layouts::site.layout>
