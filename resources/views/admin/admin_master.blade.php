<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Market OS')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/toastr/toastr.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/market-theme.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ asset('backend/assets/libs/chartjs/chart.umd.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    @stack('styles')
</head>
<body data-menu-color="dark" data-sidebar="default" class="mk-app">
<div id="app-layout">
    @include('admin.body.header')
    @include('admin.body.sidebar')

    <div class="content-page">
        @yield('admin')
        @include('admin.body.footer')
    </div>
</div>

<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/app.js') }}"></script>
<script src="{{ asset('backend/assets/libs/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
@include('admin.body.alerts')
@stack('scripts')
</body>
</html>
