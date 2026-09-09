<x-layouts::site.layout :title="$data->title" :site="$site ?? null">
    <div class="container mx-auto min-h-screen">
        <section class="bg-white lg:grid lg:h-screen lg:place-content-center">
            <div class="mx-auto w-screen max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8 lg:py-32">
                <div class="mx-auto max-w-prose text-center">
                    <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl capitalize">{{ $data->title }}</h1>

                    <p class="mt-4 text-base text-pretty text-gray-700 sm:text-lg/relaxed">{{ $data->areas }}</p>

                    <div class="mt-4 flex justify-center gap-4 sm:mt-6">
                        <a class="inline-block rounded border border-indigo-600 bg-indigo-600 px-5 py-3 font-medium text-white shadow-sm transition-colors hover:bg-indigo-700" href="#"> Get Started </a>

                        <a class="inline-block rounded border border-gray-200 px-5 py-3 font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900" href="#"> Learn More </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="">{{ $data->test_taxonomy->title ?? "" }}</div>
        @dump ($data->test_taxonomy)

        <div class="">
            <h1>{{ $data->title }}</h1>
            <div class="">{{ $data->areas }}</div>
            <div class="">{!! $data->contentss !!}</div>

            @foreach ($data->test_replicator as $test)
                <div class="">
                    <h2>{{ $test->headers }}</h2>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts::site.layout>
