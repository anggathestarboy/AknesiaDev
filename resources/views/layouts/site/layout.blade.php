@props ([
    "title" => null,
    "site" => null,
    "entry" => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $site?->lang ?? app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ filled($title) ? $title . " - " . config("app.name") : config("app.name") }}</title>
    <link rel="icon" href="/favicon.png" sizes="any" />
    @vite (["resources/css/site.css"])

    @php $siteSettings = \App\Modules\SiteSettings\Models\SiteSetting::current(); @endphp

    @if ($siteSettings->googleSearchConsoleEnabled() && $siteSettings->googleSearchConsoleVerificationCode())
        <meta name="google-site-verification" content="{{ $siteSettings->googleSearchConsoleVerificationCode() }}" />
    @endif

    @if ($siteSettings->googleAnalyticsEnabled() && $siteSettings->googleAnalyticsMeasurementId())
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $siteSettings->googleAnalyticsMeasurementId() }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '{{ $siteSettings->googleAnalyticsMeasurementId() }}');
        </script>
    @endif

    @foreach ($siteSettings->structuredDataBlocks() as $structuredData)
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach

    @if ($entry)
        @foreach ($entry->structuredDataBlocks() as $structuredData)
            <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endforeach
    @endif
</head>
<body class="">
    <x-layouts::site.header :site="$site" />
    <main class="">{{ $slot }}</main>
    <x-layouts::site.footer :site="$site" />
</body>
</html>
