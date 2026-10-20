@props([
    'site' => null,
])

@navigation ("header", ["site" => $site?->handle])
    @php
        // Path halaman saat ini, contoh: "/" atau "/about"
        $currentPath = '/' . trim(request()->path(), '/');

        // Normalisasi URL menu (mendukung URL relatif maupun absolut)
        $normalizePath = fn ($url) => '/' . trim((string) parse_url((string) $url, PHP_URL_PATH), '/');

        // Apakah URL menu sama dengan halaman saat ini
        $isUrlActive = fn ($url) => filled($url) && $url !== '#' && $normalizePath($url) === $currentPath;

        $activeHandle = $site?->handle;
    @endphp

    <header x-data="{ mobileMenuOpen: false, openDropdown: null, closeTimer: null }" x-effect="document.body.classList.toggle('overflow-hidden', mobileMenuOpen)"
        @keydown.escape.window="mobileMenuOpen = false; openDropdown = null"
        @resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false"
        class="border-b border-zinc-200 bg-white relative z-50">
        <div
            class="container mx-auto flex items-center justify-between px-4 py-3 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:gap-6 lg:px-0">
            <a href="/" class="flex items-center gap-2 justify-self-start font-bold text-xl text-black">
                @if ($navigation->site_logo)
                    <img src="{{ $navigation->site_logo->url }}" alt="Aksnesia" class="h-[60px] w-auto">
                @else
                    <span>Aksnesia</span>
                @endif
            </a>

            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-zinc-700 hover:bg-zinc-100 hover:text-red-500 lg:hidden">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Navigation Desktop -->
            <nav class="hidden lg:flex lg:items-center lg:gap-6">
                @foreach ($navigation->navbar_loop ?? [] as $index => $item)
                    @php
                        $isActive = $isUrlActive($item->navbar_url)
                            || collect($item->child_loop ?? [])->contains(fn ($child) => $isUrlActive($child->child_menu_url));
                    @endphp

                    @if ($item->menu_level && count($item->child_loop ?? []))
                        <div class="relative py-2"
                            @mouseenter="clearTimeout(closeTimer); openDropdown = 'desktop-{{ $index }}'"
                            @mouseleave="closeTimer = setTimeout(() => { openDropdown = null }, 150)">
                            <a href="{{ $item->navbar_url ?: '#' }}"
                                class="inline-flex items-center gap-1 text-sm font-medium transition-colors hover:text-red-500"
                                :class="openDropdown === 'desktop-{{ $index }}' ? 'text-red-500' : '{{ $isActive ? 'text-red-500' : 'text-zinc-700' }}'"
                                @click="if ('{{ $item->navbar_url }}' === '' || '{{ $item->navbar_url }}' === '#') { openDropdown = openDropdown === 'desktop-{{ $index }}' ? null : 'desktop-{{ $index }}' }">
                                {{ $item->navbar_label }}
                                <svg class="h-4 w-4 transition-transform duration-200"
                                    :class="openDropdown === 'desktop-{{ $index }}' ? 'rotate-180' : ''"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </a>

                            <div x-show="openDropdown === 'desktop-{{ $index }}'"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1" @click.outside="openDropdown = null"
                                x-cloak
                                class="absolute left-0 top-full z-10 min-w-48 rounded-md border border-zinc-200 bg-white py-1 shadow-lg">
                                @foreach ($item->child_loop as $child)
                                    <a href="{{ $child->child_menu_url ?: '#' }}" @class([
                                        'block px-4 py-2 text-sm hover:bg-zinc-100 hover:text-red-500',
                                        'text-red-500' => $isUrlActive($child->child_menu_url),
                                        'text-zinc-700' => !$isUrlActive($child->child_menu_url),
                                    ])>{{ $child->child_menu_label }}</a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->navbar_url ?: '#' }}" @class([
                            'text-sm font-medium transition-colors hover:text-red-500',
                            'text-red-500' => $isActive,
                            'text-zinc-700' => !$isActive,
                        ])>{{ $item->navbar_label }}</a>
                    @endif
                @endforeach
            </nav>

            <!-- Language Dropdown Desktop -->
            <div class="hidden lg:relative lg:block lg:justify-self-end lg:text-sm lg:font-medium py-2"
                @mouseenter="clearTimeout(closeTimer); openDropdown = 'desktop-lang'"
                @mouseleave="closeTimer = setTimeout(() => { openDropdown = null }, 150)">
                <button type="button" @click="openDropdown = openDropdown === 'desktop-lang' ? null : 'desktop-lang'"
                    class="inline-flex items-center gap-1 text-zinc-700 hover:text-zinc-900">
                    {{ strtoupper($site?->lang ?? app()->getLocale()) }}
                    <svg class="h-4 w-4 transition-transform duration-200"
                        :class="openDropdown === 'desktop-lang' ? 'rotate-180' : ''" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="openDropdown === 'desktop-lang'" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                    @click.outside="openDropdown = null" x-cloak
                    class="absolute right-0 top-full z-10 min-w-44 rounded-md border border-zinc-200 bg-white py-1 shadow-lg">
                    @sites
                        <a href="{{ $site->url }}" @class([
                            'block whitespace-nowrap px-4 py-2 text-zinc-700 hover:bg-zinc-100',
                            'font-semibold bg-zinc-50' => $site->handle === $activeHandle,
                        ])>{{ $site->name }}
                            ({{ strtoupper($site->lang) }})</a>
                    @endsites
                </div>
            </div>
        </div>

        <!-- Panel Menu Mobile -->
        <div x-cloak :class="mobileMenuOpen ? 'h-[calc(100dvh-65px)]' : 'h-0'"
            :aria-hidden="mobileMenuOpen ? 'false' : 'true'"
            class="fixed inset-x-0 top-[65px] z-40 h-0 w-full overflow-hidden bg-white transition-[height] duration-700 ease-in-out lg:hidden">
            <div class="flex h-full flex-col justify-between overflow-y-auto px-6 py-6">
                <nav class="flex flex-col gap-3">
                    @foreach ($navigation->navbar_loop ?? [] as $index => $item)
                        @php
                            $isActive = $isUrlActive($item->navbar_url)
                                || collect($item->child_loop ?? [])->contains(fn ($child) => $isUrlActive($child->child_menu_url));
                        @endphp

                        @if ($item->menu_level && count($item->child_loop ?? []))
                            <div class="flex flex-col">
                                <button
                                    @click="openDropdown = openDropdown === 'mobile-{{ $index }}' ? null : 'mobile-{{ $index }}'"
                                    type="button"
                                    class="flex w-full items-center justify-between py-2.5 text-lg font-bold transition-colors hover:text-red-500"
                                    :class="openDropdown === 'mobile-{{ $index }}' ? 'text-red-500' : '{{ $isActive ? 'text-red-500' : 'text-zinc-800' }}'">
                                    <span>{{ $item->navbar_label }}</span>
                                    <div
                                        class="flex items-center justify-center rounded-full bg-zinc-100 p-1.5 text-zinc-700">
                                        <svg class="h-4 w-4 transition-transform duration-200"
                                            :class="openDropdown === 'mobile-{{ $index }}' ? '-rotate-90' : ''"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                                <div x-show="openDropdown === 'mobile-{{ $index }}'" x-collapse x-cloak
                                    class="flex flex-col gap-2 pt-1 pb-2">
                                    @foreach ($item->child_loop as $child)
                                        <a href="{{ $child->child_menu_url ?: '#' }}" @class([
                                            'inline-flex items-center gap-1.5 py-2 text-sm font-medium hover:text-red-500',
                                            'text-red-500' => $isUrlActive($child->child_menu_url),
                                            'text-zinc-600' => !$isUrlActive($child->child_menu_url),
                                        ])>
                                            <span>{{ $child->child_menu_label }}</span>
                                            <svg class="h-3 w-3 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                                stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->navbar_url ?: '#' }}" @class([
                                'py-2.5 text-lg font-bold transition-colors hover:text-red-500',
                                'text-red-500' => $isActive,
                                'text-zinc-800' => !$isActive,
                            ])>{{ $item->navbar_label }}</a>
                        @endif
                    @endforeach
                </nav>

                <div class="mt-8 border-t border-zinc-200 pt-6 pb-6">
                    <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Language</div>
                    <div class="flex items-center gap-2">
                        @sites
                            @php($langCode = strtolower($site->lang))
                            <a href="{{ $site->url }}" @class([
                                'inline-flex items-center justify-center h-10 w-12 rounded border text-xs font-bold transition-colors uppercase',
                                'border-zinc-900 bg-zinc-900 text-white' => $site->handle === $activeHandle,
                                'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' =>
                                    $site->handle !== $activeHandle,
                            ])>
                                {{ $langCode }}
                            </a>
                        @endsites
                    </div>
                </div>
            </div>
        </div>
    </header>
@endnavigation