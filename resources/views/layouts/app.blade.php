<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/polibatam.png') }}">
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="app">
    <div class="sidebar">
        <div class="brand-plate brand-plate-top">
            <img src="{{ asset('images/polibatam.png') }}" alt="Politeknik Negeri Batam">
        </div>
        <div class="nav">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="16" width="7" height="5" rx="1.5"></rect></svg>
                Dashboard
            </a>
            <a href="{{ route('rack.index') }}" class="nav-item {{ request()->routeIs('rack.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                Rack Monitoring
            </a>
            <a href="{{ route('transaksi') }}" class="nav-item {{ request()->routeIs('transaksi') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15.5 14"></polyline></svg>
                Transaction History
            </a>
            <a href="{{ route('laporan.index') }}" class="nav-item {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line></svg>
                Reports
            </a>

            <div class="nav-section-label nav-section-label-master">Master Data</div>
            <a href="{{ route('master.components.index') }}" class="nav-item nav-item-master {{ request()->routeIs('master.components.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                Component Master
            </a>

            <div class="nav-section-label nav-section-label-in">Inbound</div>
            <a href="{{ route('inbound.suppliers.index') }}" class="nav-item nav-item-in {{ request()->routeIs('inbound.suppliers.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                Suppliers
            </a>
            <a href="{{ route('inbound.asns.index') }}" class="nav-item nav-item-in {{ request()->routeIs('inbound.asns.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="m9 15 2 2 4-4"></path></svg>
                ASN
            </a>
            <a href="{{ route('inbound.grns.index') }}" class="nav-item nav-item-in {{ request()->routeIs('inbound.grns.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><line x1="12" y1="22" x2="12" y2="12"></line></svg>
                Goods Receive
            </a>
            <a href="{{ route('inbound.putaways.index') }}" class="nav-item nav-item-in {{ request()->routeIs('inbound.putaways.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Put Away
            </a>
            <div class="nav-section-label nav-section-label-out">Outbound</div>
            <a href="{{ route('outbound.customers.index') }}" class="nav-item nav-item-out {{ request()->routeIs('outbound.customers.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><circle cx="9" cy="7" r="4"></circle><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"></path></svg>
                Customers
            </a>
            <a href="{{ route('outbound.sales-orders.index') }}" class="nav-item nav-item-out {{ request()->routeIs('outbound.sales-orders.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="m9 15 2 2 4-4"></path></svg>
                Sales Order
            </a>
            <a href="{{ route('outbound.pickings.index') }}" class="nav-item nav-item-out {{ request()->routeIs('outbound.pickings.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Picking
            </a>
            <a href="{{ route('outbound.delivery-orders.index') }}" class="nav-item nav-item-out {{ request()->routeIs('outbound.delivery-orders.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                Delivery Order
            </a>
        </div>
        <div class="brand-plate brand-plate-bottom">
            <img src="{{ asset('images/MMG-Logo.png') }}" alt="Multi Mitra Guna">
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('title', 'Dashboard')</div>
            <div class="topbar-meta" id="clock"></div>
        </div>
        <div class="content">
            @yield('content')
        </div>
    </div>
</div>

<script>
    function tickClock(){
        const d = new Date();
        const dateStr = d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}).toUpperCase().replace(/\./g,'');
        const pad = n => String(n).padStart(2, '0');
        const timeStr = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        document.getElementById('clock').textContent = dateStr + ' · ' + timeStr;
    }
    tickClock(); setInterval(tickClock, 1000);
</script>
@stack('scripts')
</body>
</html>
