@extends('AdminPanel.admin-layout')

@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
  <style>
    table th, table td {
      white-space: nowrap;
      vertical-align: middle;
    }
    img.product-image {
      width: 50px;
      border-radius: 5px;
    }
  </style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h5 class="pb-2 mb-4">Assigned Orders</h5>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter Form --}}
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="vendor_id" class="form-label">Vendor</label>
            <select class="form-select" name="vendor_id" id="vendor_id">
                <option value="">All Vendors</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                        {{ $vendor->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" name="status" id="status">
                <option value="">All Status</option>
                @foreach(['assigned', 'accepted', 'shipped', 'in_transit', 'delivered', 'rejected'] as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label for="from_date" class="form-label">From</label>
            <input type="date" class="form-control" name="from_date" value="{{ request('from_date') }}">
        </div>

        <div class="col-md-2">
            <label for="to_date" class="form-label">To</label>
            <input type="date" class="form-control" name="to_date" value="{{ request('to_date') }}">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    {{-- Orders Table --}}
    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="orderTable" class="table table-striped table-bordered nowrap w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Vendor</th>
                        <th>Product Image</th>
                        <th>Product Name</th>
                        <th>Order Quantity</th>
                        <th>Status</th>
                        <th>Assigned At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $key => $order)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $order['order_number'] }}</td>
                        <td>{{ $order['vendor_name'] ?? 'N/A' }}</td>
                        <td>
                            @if($order['image'])
                                <img src="{{ $order['image'] }}" alt="Product" class="product-image">
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $order['title'] }}</td>
                        <td>{{ $order['quantity'] }}</td>
                        <td>
                            @php
                                $badgeClass = match($order['status']) {
                                    'assigned'     => 'secondary',
                                    'accepted'     => 'info',
                                    'shipped'      => 'dark',
                                    'in_transit'   => 'warning',
                                    'delivered'    => 'success',
                                    'rejected'     => 'danger',
                                    default        => 'light'
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeClass }}">
                                {{ ucwords(str_replace('_', ' ', $order['status'])) }}
                            </span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d-m-Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('admin.vendor.assigned.product.details', ['order_id' => $order['order_id'], 'product_id' => $order['product_id']]) }}" class="btn btn-sm btn-info">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No assigned orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
  <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive-bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/jszip/jszip.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/pdfmake/pdfmake.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/pdfmake/vfs_fonts.js') }}"></script>

  <script>
    $(document).ready(function () {
      $('#orderTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        ordering: true,
        responsive: false,
        scrollX: true
      });
    });
  </script>
@endsection
