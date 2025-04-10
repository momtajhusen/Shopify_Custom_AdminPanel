<ul class="menu-inner py-1">
  <li class="menu-item {{ request()->routeIs('admin.dashboard.overview') ? 'active' : '' }}">
    <a href="{{ route('admin.dashboard.overview') }}" class="menu-link">
      <i class="menu-icon icon-base ti tabler-smart-home"></i>
      <div>Dashboard</div>
    </a>
  </li>

  <li class="menu-item {{ request()->routeIs('vendors.*') ? 'active' : '' }}">
    <a href="{{ route('vendors.index') }}" class="menu-link">
      <i class="menu-icon icon-base ti tabler-building-store"></i>
      <div>Vendors</div>
    </a>
  </li>

  <li class="menu-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
    <a href="{{ route('orders.index') }}" class="menu-link">
      <i class="menu-icon icon-base ti tabler-shopping-cart"></i>
      <div>Orders</div>
    </a>
  </li>

  <li class="menu-item {{ request()->routeIs('admin.vendor.report') ? 'active' : '' }}">
    <a href="{{ route('admin.vendor.report') }}" class="menu-link">
      <i class="menu-icon icon-base ti tabler-report-analytics"></i>
      <div>Report</div>
    </a>
  </li>

  <li class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
    <a href="{{ route('admin.settings') }}" class="menu-link">
      <i class="menu-icon icon-base ti tabler-settings"></i>
      <div>Settings</div>
    </a>
  </li>
</ul>
