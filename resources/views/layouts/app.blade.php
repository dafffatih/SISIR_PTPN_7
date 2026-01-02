<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'SISIR')</title>
</head>
<body style="margin:0; font-family:Arial, sans-serif; background:#f1f5f9;">
  <div style="display:flex;">
    @include('layouts.sidebar')

    <main style="flex:1; padding:24px;">
      @yield('content')
    </main>
  </div>
</body>
</html>
