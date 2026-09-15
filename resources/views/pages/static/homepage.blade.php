<x-layouts::site.layout :title="$data->heading ?? $data->title" :site="$site ?? null">
    <div class="mx-auto">
        <section class="bg-white lg:grid lg:place-content-center">
            <div class="mx-auto w-screen max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-32">
                <div class="mx-auto max-w-prose text-center">
                    <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl">{{ $data->heading }}</h1>

                    @if ($data->subheading)
                        <p class="mt-4 text-base text-pretty text-gray-700 sm:text-lg/relaxed">{{ $data->subheading }}</p>
                    @endif
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-3xl px-6 py-12 prose">{!! $data->contentss !!}</div>

        <div class="mx-auto max-w-xl px-6 pb-12">
            @foreach ($data->pilih_gambar ?? [] as $image)
                <img src="{{ $image->url }}" alt="" class="w-full rounded-lg" />
            @endforeach
        </div>
    </div>
</x-layouts::site.layout>
