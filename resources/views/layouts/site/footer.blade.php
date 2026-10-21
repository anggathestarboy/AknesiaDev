@props([
    'site' => null,
])


@global('footer')
    <footer class="bg-gray-100 text-black mt-26 sm:mt-0">
        <div class="container relative mx-auto px-5 lg:px-0 py-12">
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
                                    <li><a href="{{ $data->footer_link_item_link }}" class="hover:text-red-600">{{ $data->footer_link_item_label }}</a></li>
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
                        @foreach ($global->social_media_loop as $social)
                            <a href="{{ $social->social_media_link }}" target="_blank" aria-label="{{ $social->social_media_label }}"
                                class="w-9 h-9 rounded-full border border-black/60 flex items-center justify-center hover:text-red-600 hover:border-red-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @switch($social->social_media_label)
                                        @case('instagram')
                                            <path d="M12 2.2c3.2 0 3.6 0 4.8.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-3.3-.1-4.8-1.7-4.9-4.9C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8C2.4 3.9 4 2.4 7.2 2.3 8.4 2.2 8.8 2.2 12 2.2zM12 7a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zm5.2-9.7a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z" />
                                            @break
                                        @case('tiktok')
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                                            @break
                                        @case('youtube')
                                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                            @break
                                        @case('facebook')
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                            @break
                                    @endswitch
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="border-t border-black/40 mt-10 pt-6 flex flex-col md:flex-row justify-between gap-4 text-sm">
                <p>&copy;{{ now()->year }} Aksnesia</p>
                <div class="flex flex-wrap gap-4">
                    @foreach ($global->footer_copyright_loop as $copyright)
                        <a href="{{ $copyright->footer_copyright_link }}" class="hover:text-red-600">{{ $copyright->footer_copyright_label }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
@endglobal