@php
    $sidebarTreeService = app(\App\Modules\Navigation\Services\NavigationTreeService::class);
    $sidebarNavigation = \App\Modules\Navigation\Models\Navigation::where("handle", "sidebar-admin")->first();
    $sidebarItems = $sidebarNavigation ? $sidebarTreeService->load($sidebarNavigation) : [];

    // Whitelist-only: an item only shows to a user whose role is checked in
    // its own "Visible to" list (see the sidebar-admin tree editor) — a
    // Super Admin sees everything regardless (see User::isSuper()). A group
    // left with no visible children is dropped too, since it never links
    // anywhere on its own, only toggles a now-empty submenu.
    $sidebarUserRoleHandles = auth()->user()?->isSuper() ? null : auth()->user()?->roles->pluck("handle")->all() ?? [];

    $sidebarFilterByRole = function (array $items) use (&$sidebarFilterByRole, $sidebarUserRoleHandles): array {
        $visible = [];

        foreach ($items as $item) {
            if ($sidebarUserRoleHandles !== null && array_intersect($sidebarUserRoleHandles, $item["roles"] ?? []) === []) {
                continue;
            }

            $hadChildren = ($item["children"] ?? []) !== [];
            $item["children"] = $sidebarFilterByRole($item["children"] ?? []);

            if ($hadChildren && $item["children"] === []) {
                continue;
            }

            $visible[] = $item;
        }

        return $visible;
    };

    $sidebarItems = $sidebarFilterByRole($sidebarItems);

    if (!config("activity-log.enabled")) {
        $sidebarItems = array_values(array_filter($sidebarItems, fn(array $item): bool => ($item["url"] ?? null) !== route("activity-log.activity-log.index", absolute: false)));
    }

    $sidebarIsCurrent = function (?string $url): bool {
        if (!$url) {
            return false;
        }

        $path = ltrim($url, "/");

        return request()->is($path === "" ? "/" : $path);
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include ("components.partials.head")
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-partials.app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <a href="/" target="_blank" class="hidden lg:block">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.37 7.657c2.063.528 2.396 2.806 3.202 3.87 1.07 1.413 2.075 1.228 3.192 2.644 1.805 2.289 1.312 5.705 1.312 6.705M20 15h-1a4 4 0 0 0-4 4v1M8.587 3.992c0 .822.112 1.886 1.515 2.58 1.402.693 2.918.351 2.918 2.334 0 .276 0 2.008 1.972 2.008 2.026.031 2.026-1.678 2.026-2.008 0-.65.527-.9 1.177-.9H20M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </a>

            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group class="grid">
                @foreach ($sidebarItems as $item)
                    @if (($item["children"] ?? []) !== [])
                        @php
                            $parentUrl = $sidebarTreeService->resolveUrl($item);
                            $parentIsCurrent = $sidebarIsCurrent($parentUrl) || collect($item["children"])->contains(fn($child) => $sidebarIsCurrent($sidebarTreeService->resolveUrl($child)));
                        @endphp
                        <div x-data="{ open: {{ $parentIsCurrent ? 'true' : 'false' }} }" class="flex flex-col">
                            <a
                                href="{{ $parentUrl ?? '#' }}"
                                target="{{ $item['target'] }}"
                                wire:navigate
                                x-on:click="open = !open"
                                @if ($parentIsCurrent) data-current @endif
                                class="my-px flex h-8 w-full items-center gap-3 rounded-lg border border-transparent px-3 text-start text-sm font-medium text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 data-current:border-zinc-200 data-current:bg-white data-current:text-(--color-accent-content) dark:text-white/80 dark:hover:bg-white/7 dark:hover:text-white dark:data-current:border-transparent dark:data-current:bg-white/7"
                            >
                                <span class="flex-1 truncate text-start">{{ $item["title"] }}</span>
                                <flux:icon.chevron-right x-show="!open" x-cloak class="size-4 shrink-0 text-zinc-500 dark:text-white/80" />
                                <flux:icon.chevron-down x-show="open" x-cloak class="size-4 shrink-0 text-zinc-500 dark:text-white/80" />
                            </a>

                            <div x-show="open" x-cloak class="flex flex-col">
                                @foreach ($item["children"] as $child)
                                    @php $childUrl = $sidebarTreeService->resolveUrl($child); @endphp
                                    <a
                                        href="{{ $childUrl ?? '#' }}"
                                        target="{{ $child['target'] }}"
                                        wire:navigate
                                        @if ($sidebarIsCurrent($childUrl)) data-current @endif
                                        class="my-px flex h-7 w-full items-center rounded-lg border border-transparent ps-8 pe-3 text-xs text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 data-current:border-zinc-200 data-current:bg-white data-current:text-(--color-accent-content) dark:text-white/80 dark:hover:bg-white/7 dark:hover:text-white dark:data-current:border-transparent dark:data-current:bg-white/7"
                                    >
                                        {{ $child["title"] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @php $itemUrl = $sidebarTreeService->resolveUrl($item); @endphp
                        <flux:sidebar.item :href="$itemUrl ?? '#'" :current="$sidebarIsCurrent($itemUrl)" target="{{ $item['target'] }}" wire:navigate> {{ $item["title"] }} </flux:sidebar.item>
                    @endif
                @endforeach
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        {{-- <flux:sidebar.nav> --}}
        {{-- <flux:sidebar.item href="/" target="_blank"> {{ __("Visit Site") }} </flux:sidebar.item> --}}
        {{-- <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank"> {{ __("Documentation") }} </flux:sidebar.item> --}}
        {{-- </flux:sidebar.nav> --}}

        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="px-2">
            <flux:radio value="light" icon="sun" class="cursor-pointer" />
            <flux:radio value="dark" icon="moon" class="cursor-pointer" />
            <flux:radio value="system" icon="computer-desktop" class="cursor-pointer" />
        </flux:radio.group>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <a href="/" target="_blank" class="mr-3">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.37 7.657c2.063.528 2.396 2.806 3.202 3.87 1.07 1.413 2.075 1.228 3.192 2.644 1.805 2.289 1.312 5.705 1.312 6.705M20 15h-1a4 4 0 0 0-4 4v1M8.587 3.992c0 .822.112 1.886 1.515 2.58 1.402.693 2.918.351 2.918 2.334 0 .276 0 2.008 1.972 2.008 2.026.031 2.026-1.678 2.026-2.008 0-.65.527-.9 1.177-.9H20M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </a>

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate> {{ __("Settings") }} </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button"> {{ __("Log out") }} </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @persist ("toast")
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
