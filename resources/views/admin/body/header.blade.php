<div class="topbar-custom">
    <div class="container-xxl">
        <div class="d-flex justify-content-between align-items-center">
            <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center gap-2">
                <li>
                    <button class="button-toggle-menu nav-link ps-0">
                        <i data-feather="menu" class="noti-icon"></i>
                    </button>
                </li>
                <li class="d-none d-md-block">
                    <span class="mk-top-chip">
                        <i data-feather="calendar" style="width:14px;height:14px"></i>
                        {{ now()->format('D, d M Y') }}
                    </span>
                </li>
            </ul>

            <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                @php
                    $profileData = Auth::user();
                @endphp

                <li class="dropdown notification-list topbar-dropdown">
                    <a class="nav-link dropdown-toggle nav-user me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="{{ (!empty($profileData->photo)) ? url('upload/user_images/'.$profileData->photo) : url('upload/no_image.jpg') }}" alt="user-image" class="rounded-circle">
                        <span class="pro-user-name ms-1">
                            {{ $profileData->name }} <i class="mdi mdi-chevron-down"></i>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome</h6>
                        </div>
                        <a href="{{ route('admin.profile') }}" class="dropdown-item notify-item">
                            <i class="mdi mdi-account-circle-outline fs-16 align-middle"></i>
                            <span>My Account</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf
                            <button type="submit" class="dropdown-item notify-item">
                                <i class="mdi mdi-location-exit fs-16 align-middle"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
