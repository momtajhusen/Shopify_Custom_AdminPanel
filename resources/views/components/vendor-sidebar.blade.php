<ul class="menu-inner py-1">
    <li class="menu-item {{ request()->routeIs('vendor.dashboard.overview') ? 'active' : '' }}">
      <a href="{{ route('vendor.dashboard.overview') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-dashboard"></i>
        <div>Dashboard</div>
      </a>
    </li>
  
    <li class="menu-item {{ request()->routeIs('vendor.my.orders') ? 'active' : '' }}">
      <a href="{{ route('vendor.my.orders') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-shopping-cart"></i>
        <div>My Orders</div>
      </a>
    </li>
  </ul>
  