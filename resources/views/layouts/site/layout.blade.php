@props ([
    "title" => null,
    "site" => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $site?->lang ?? app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ filled($title) ? $title . " - " . config("app.name") : config("app.name") }}</title>
    <link rel="icon" href="/favicon.png" sizes="any" />
    @vite (["resources/css/site.css"])
</head>
<body class="">
    <x-layouts::site.header :site="$site" />
    <main class="">{{ $slot }}</main>
    <x-layouts::site.footer :site="$site" />
</body>
</html>
