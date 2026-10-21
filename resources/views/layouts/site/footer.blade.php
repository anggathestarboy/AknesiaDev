@props([
    'site' => null,
])


@global('footer')
    <footer class="bg-gray-100 text-black">
        <div class="container relative mx-auto px-4 py-12">
            <a href="#"
                class="absolute right-4 top-12 z-10 w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
                </svg>
            </a>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div>
                    <h2 class="text-3xl md:text-4xl lg:max-w-96 pr-16 lg:pr-0 font-bold leading-tight">
                        {{ $global->footer_label }}
                    </h2>
                    <a href="#" class="inline-block mt-8 bg-red-600 text-white font-semibold px-6 py-3 rounded-full">
                        {{ $global->footer_button_label }} &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    @foreach ($global->footer_link_loop as $footer)
                        <div>
                            <h3 class="text-xl font-bold mb-3">{{ $footer->footer_link_heading }}</h3>

                            <ul class="space-y-3">
                                @foreach ($footer->footer_link_item_loop as $data)
                                    <li><a href="{{ $data->footer_link_item_link }}">{{ $data->footer_link_item_label }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mt-10 lg:mt-16">
                <div>
                    <img src="{{ $global->site_footer_logo->url }}" alt="qwdqdp" class="w-20 h-20 object-contain" />
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-3">{{ $site?->name == 'Indonesia' ? 'Ikuti Kami' : 'Follow Us' }}</h3>
                    <div class="flex gap-3">
                        <a href="#"
                            class="w-9 h-9 rounded-full border border-black/60 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.2c3.2 0 3.6 0 4.8.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-3.3-.1-4.8-1.7-4.9-4.9C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8C2.4 3.9 4 2.4 7.2 2.3 8.4 2.2 8.8 2.2 12 2.2zM12 7a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zm5.2-9.7a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="w-9 h-9 rounded-full border border-black/60 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.8 14.2c-.2.7-1.4 1.3-1.9 1.3-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5.1-4.5-.1-.2-1.2-1.6-1.2-3s.8-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.8-1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.1.1.7-.1 1.3z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="w-9 h-9 rounded-full border border-black/60 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M4.98 3.5a2.5 2.5 0 11-.01 5 2.5 2.5 0 01.01-5zM3 9.75h4V21H3zM9.75 9.75h3.8v1.6h.1c.5-1 1.8-2 3.7-2 4 0 4.7 2.6 4.7 6V21h-4v-4.9c0-1.2 0-2.7-1.7-2.7s-1.9 1.3-1.9 2.6V21h-4z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-black/40 mt-10 pt-6 flex flex-col md:flex-row justify-between gap-4 text-sm">
                <p>&copy;{{ now()->year }} Aksnesia</p>
                <div class="flex flex-wrap gap-4">
                    @foreach ($global->footer_copyright_loop as $copyright)
                        <a href="{{ $copyright->footer_copyright_link }}">{{ $copyright->footer_copyright_label }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
@endglobal