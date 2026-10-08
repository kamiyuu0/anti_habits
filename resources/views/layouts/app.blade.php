@php
    $ogpImage = trim($__env->yieldContent('ogp_image')) ?: 'https://res.cloudinary.com/antihabits/image/upload/v1757773129/anti_habits_static_ogp_zg7q8j.png';
    $siteDescription = '「Anti Habits」は、悪習慣排除を手助けするアプリです。';
@endphp
<!DOCTYPE html>
<html lang="ja" data-theme="nord">
  <head>
    <meta charset="utf-8">
    <title>Anti Habits</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#edeff4">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="description" content="{{ $siteDescription }}">
    <link rel="canonical" href="{{ url()->full() }}">
    <meta property="og:site_name" content="Anti Habits">
    <meta property="og:title" content="Anti Habits">
    <meta property="og:description" content="{{ $siteDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:image" content="{{ $ogpImage }}">
    <meta property="og:locale" content="ja-JP">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $ogpImage }}">

    @stack('head')

    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/icon.png" type="image/png">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://use.fontawesome.com/releases/v5.15.1/css/all.css" rel="stylesheet">

    @production
      <!-- Google tag (gtag.js) -->
      <script async src="https://www.googletagmanager.com/gtag/js?id=G-7D7PDP4R2S"></script>
      <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-7D7PDP4R2S');
      </script>
    @endproduction
  </head>

  <body class="">
    <div class="sticky lg:top-0 z-2">
      @include('shared.header')
    </div>
    <main class="pb-16 min-h-screen bg-base-200">
      @include('shared.flash_messages')
      @yield('content')
    </main>
    @include('shared.footer')
  </body>
</html>
