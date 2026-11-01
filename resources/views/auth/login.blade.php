<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Login — Market OS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,550;9..144,700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/libs/toastr/toastr.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/market-theme.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="mk-login-body">
<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">
        <div class="col-xl-5 d-flex align-items-center">
            <div class="w-100 px-3 px-md-5 py-5">
                <div class="mk-login-panel mx-auto" style="max-width: 420px">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="mk-brand-mark">M</span>
                        <div>
                            <div class="mk-display fw-semibold fs-4 mb-0">Market OS</div>
                            <small class="text-muted">Supermarket management</small>
                        </div>
                    </div>

                    <h4 class="mb-1">Admin login</h4>
                    <p class="text-muted mb-4">Sign in to your dashboard</p>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input class="form-control form-control-lg" name="email" type="email" id="email" value="{{ old('email') }}" required autofocus placeholder="admin@gmail.com">
                            @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input class="form-control form-control-lg" name="password" type="password" required id="password" placeholder="••••••••">
                            @error('password')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a class="text-muted small" href="{{ route('password.request') }}">Forgot password?</a>
                        </div>
                        <button class="btn btn-primary btn-lg w-100" type="submit" style="border-radius:14px;background:linear-gradient(135deg,#1a7a52,#0f3d2e);border:0">
                            Enter dashboard
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7 mk-login-side">
            <div style="max-width:520px">
                <h2>Run your market with clarity</h2>
                <p class="mb-0" style="opacity:.85;font-size:1.05rem;line-height:1.6">
                    Track sales, stock, and profit from the first screen — then sell, restock, and close cash with confidence.
                </p>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('backend/assets/libs/toastr/toastr.min.js') }}"></script>
@if(session('message'))
<script>
toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4000 };
@php $toastType = in_array(session('alert-type'), ['success', 'error', 'warning', 'info'], true) ? session('alert-type') : 'info'; @endphp
toastr[@json($toastType)](@json(session('message')));
</script>
@endif
</body>
</html>
