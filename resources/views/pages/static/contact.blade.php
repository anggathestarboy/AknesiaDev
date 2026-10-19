<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">

    @php
        $overviewImage = $data->hero_image ?? null;
        $overviewImage = is_array($overviewImage)
            ? \App\Modules\Media\Models\AssetMeta::find(reset($overviewImage) ?: null)
            : $overviewImage;
    @endphp

    <section class="relative w-full h-[320px] md:h-[380px] lg:h-[420px] overflow-hidden bg-black text-white">
        <img src="{{ $overviewImage?->url }}" alt="{{ $data->heading ?? 'Banner' }}"
            class="absolute inset-0 w-full h-full object-cover" />

        <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/50 to-black/10"></div>

        <div class="relative z-10 container mx-auto h-full px-4 lg:px-0 flex flex-col justify-center">
            <div class="max-w-xl">
                <nav class="flex items-center gap-2 text-sm text-zinc-200 mb-4">
                    <a href="/" class="hover:text-white transition-colors">Beranda</a>
                    <span class="text-zinc-400">&gt;</span>
                    <span class="font-bold text-white">{{ $data->hero_heading }}</span>
                </nav>

                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight text-white mb-4">
                    {{ $data->hero_heading }}
                </h1>

                <p class="text-sm md:text-base text-zinc-200 leading-relaxed">
                    {{ $data->hero_desc }}
                </p>
            </div>
        </div>
    </section>



    <section class="w-full py-20 lg:16 bg-white">
    <div class="container mx-auto px-4 lg:px-0">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">

            <div>
                <h2 class="text-xl font-bold text-zinc-900 mb-2">
                    Ikuti Kami
                </h2>

                <p class="text-sm text-zinc-500 mb-5 max-w-md">
                    Tetap terhubung dengan kami melalui media sosial untuk mendapatkan informasi dan update terbaru.
                </p>

                <div class="flex items-center gap-3">

                    <a href="#" target="_blank" aria-label="Instagram"
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-900 hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12 2C9.284 2 8.944 2.012 7.877 2.06c-1.065.049-1.792.218-2.428.465a4.902 4.902 0 0 0-1.771 1.153A4.902 4.902 0 0 0 2.525 5.45c-.247.636-.416 1.363-.465 2.428C2.012 8.944 2 9.284 2 12s.012 3.056.06 4.122c.049 1.065.218 1.792.465 2.428a4.902 4.902 0 0 0 1.153 1.771 4.902 4.902 0 0 0 1.771 1.153c.636.247 1.363.416 2.428.465C8.944 21.988 9.284 22 12 22s3.056-.012 4.122-.06c1.065-.049 1.792-.218 2.428-.465a4.902 4.902 0 0 0 1.771-1.153 4.902 4.902 0 0 0 1.153-1.771c.247-.636.416-1.363.465-2.428.048-1.066.06-1.406.06-4.123s-.012-3.056-.059-4.122c-.049-1.065-.218-1.792-.465-2.428a4.902 4.902 0 0 0-1.153-1.771A4.902 4.902 0 0 0 18.55 2.525c-.636-.247-1.363-.416-2.428-.465C15.056 2.012 14.716 2 12 2Zm0 1.802c2.67 0 2.986.01 4.04.059.976.044 1.505.207 1.858.344.467.182.8.399 1.15.748.35.35.566.683.748 1.15.137.353.3.882.344 1.857.048 1.055.059 1.37.059 4.04 0 2.67-.01 2.986-.059 4.04-.044.976-.207 1.505-.344 1.858-.182.466-.399.8-.748 1.15-.35.35-.683.566-1.15.748-.353.137-.882.3-1.857.344-1.054.048-1.37.059-4.041.059-2.67 0-2.986-.01-4.04-.059-.976-.044-1.505-.207-1.858-.344a3.097 3.097 0 0 1-1.15-.748 3.098 3.098 0 0 1-.748-1.15c-.137-.353-.3-.882-.344-1.857-.048-1.055-.059-1.37-.059-4.041 0-2.67.01-2.986.059-4.04.044-.976.207-1.505.344-1.858.182-.466.399-.8.748-1.15.35-.35.683-.566 1.15-.748.353-.137.882-.3 1.857-.344 1.055-.048 1.37-.059 4.041-.059Zm0 3.063a5.135 5.135 0 1 0 0 10.27 5.135 5.135 0 0 0 0-10.27Zm0 8.468a3.333 3.333 0 1 1 0-6.666 3.333 3.333 0 0 1 0 6.666Zm6.538-8.671a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0Z"
                                fill="white" />
                        </svg>
                    </a>

                    <a href="#" target="_blank" aria-label="Facebook"
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-900 hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M22 12.06C22 6.505 17.523 2 12 2S2 6.505 2 12.06c0 5.02 3.657 9.184 8.438 9.94v-7.03H7.898v-2.91h2.54V9.845c0-2.526 1.492-3.922 3.776-3.922 1.094 0 2.238.197 2.238.197v2.475h-1.26c-1.242 0-1.63.775-1.63 1.571v1.887h2.773l-.443 2.91h-2.33V22c4.78-.756 8.438-4.92 8.438-9.94Z"
                                fill="white" />
                        </svg>
                    </a>

                    <a href="#" target="_blank" aria-label="YouTube"
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-900 hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M23.5 6.186a3.013 3.013 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.378.55A3.013 3.013 0 0 0 .5 6.186C0 8.07 0 12 0 12s0 3.93.5 5.814a3.013 3.013 0 0 0 2.122 2.136C4.495 20.5 12 20.5 12 20.5s7.505 0 9.378-.55a3.013 3.013 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.5-5.814ZM9.6 15.5v-7l6.4 3.5-6.4 3.5Z"
                                fill="white" />
                        </svg>
                    </a>

                    <a href="#" target="_blank" aria-label="TikTok"
                        class="flex items-center justify-center w-10 h-10 rounded-full bg-zinc-900 hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M16.6 5.82c-.956-.906-1.56-2.072-1.656-3.333V2h-3.345v14.14a2.593 2.593 0 0 1-4.667 1.556 2.593 2.593 0 0 1 2.074-4.153c.278 0 .543.044.794.123v-3.4a5.923 5.923 0 0 0-.794-.054A5.927 5.927 0 0 0 3 16.14a5.927 5.927 0 0 0 5.927 5.927 5.927 5.927 0 0 0 5.927-5.927V9.01a9.13 9.13 0 0 0 5.33 1.705V7.37a5.627 5.627 0 0 1-3.584-1.55Z"
                                fill="white" />
                        </svg>
                    </a>

                </div>
            </div>

            <div>
                {{-- Field markup/labels come from the "contact" form's blueprint,
                     rendered and submitted by the Forms module's Livewire
                     component — submissions land in Admin > Forms > Contact. --}}
                <livewire:forms::public-form handle="contact" />
            </div>

        </div>
    </div>
</section>

</x-layouts::site.layout>
