<div class="app-sidebar-menu">
    <div class="h-100" data-simplebar>
        <div id="sidebar-menu">
            <div class="logo-box">
                <a href="{{ route('dashboard') }}" class="mk-brand">
                    <span class="mk-brand-mark">M</span>
                    <span class="mk-brand-text">
                        <strong>Market OS</strong>
                        <span>Supermarket desk</span>
                    </span>
                </a>
            </div>

            <ul id="side-menu">
                <li class="menu-title">Start</li>
                <li>
                    <a href="{{ route('dashboard') }}" class="tp-link">
                        <i data-feather="home"></i><span> Dashboard </span>
                    </a>
                </li>

                <li class="menu-title mt-2">Daily Work</li>
                <li>
                    <a href="{{ route('pos.index') }}" class="tp-link">
                        <i data-feather="shopping-cart"></i><span> Sell (POS) </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('cash-register.index') }}" class="tp-link">
                        <i data-feather="briefcase"></i><span> Cash Register </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('sales.index') }}" class="tp-link">
                        <i data-feather="list"></i><span> Today Sales </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('customer-payments.create') }}" class="tp-link">
                        <i data-feather="dollar-sign"></i><span> Take Debt Payment </span>
                    </a>
                </li>

                <li class="menu-title mt-2">Add Stock</li>
                <li>
                    <a href="{{ route('purchases.create') }}" class="tp-link">
                        <i data-feather="plus-circle"></i><span> New Purchase </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('purchases.index') }}" class="tp-link">
                        <i data-feather="shopping-bag"></i><span> Purchase List </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('products.index') }}" class="tp-link">
                        <i data-feather="package"></i><span> Products </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('categories.index') }}" class="tp-link">
                        <i data-feather="grid"></i><span> Categories </span>
                    </a>
                </li>

                <li class="menu-title mt-2">People</li>
                <li>
                    <a href="{{ route('customers.index') }}" class="tp-link">
                        <i data-feather="users"></i><span> Customers </span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('suppliers.index') }}" class="tp-link">
                        <i data-feather="truck"></i><span> Suppliers </span>
                    </a>
                </li>

                <li class="menu-title mt-2">More</li>
                <li>
                    <a href="#sidebarMore" data-bs-toggle="collapse">
                        <i data-feather="more-horizontal"></i><span> More Tools </span><span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarMore">
                        <ul class="nav-second-level">
                            <li><a href="{{ route('inventory.index') }}" class="tp-link">Stock / Expiry</a></li>
                            <li><a href="{{ route('expenses.index') }}" class="tp-link">Expenses</a></li>
                            <li><a href="{{ route('sale-returns.index') }}" class="tp-link">Sale Returns</a></li>
                            <li><a href="{{ route('purchase-returns.index') }}" class="tp-link">Purchase Returns</a></li>
                            <li><a href="{{ route('reports.index') }}" class="tp-link">Reports</a></li>
                            <li><a href="{{ route('settings.edit') }}" class="tp-link">Settings</a></li>
                            <li><a href="{{ route('backups.index') }}" class="tp-link">Backups</a></li>
                            <li><a href="{{ route('admin.profile') }}" class="tp-link">Profile</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
