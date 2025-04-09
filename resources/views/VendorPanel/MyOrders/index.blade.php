@extends('AdminPanel.admin-layout')

@section('styles')
  {{-- Vuexy DataTables Styles --}}
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />

  <style>
    table th, table td {
      white-space: nowrap;
    }
  </style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h5 class="pb-2 mb-4">Orders</h5>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Orders Table --}}
    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="orderTable" class="table table-striped table-bordered nowrap w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product ID</th>
                        <th>Product Image</th>
                        <th>Product Name</th>
                        <th>Order Quantity</th>
                        <th>Status</th>
                        <th>Ordered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $key => $order)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $order['product_id'] }}</td>
                        <td>
                            @if($order['image'])
                                <img src="{{ $order['image'] }}" alt="Product" style="width: 50px; border-radius:5px">
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
                                    'in_process'   => 'warning',
                                    'ready'        => 'primary',
                                    'shipped'      => 'dark',
                                    'in_transit'   => 'orange',
                                    'delivered'    => 'success',
                                    default        => 'light'
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeClass }}">
                                {{ ucwords(str_replace('_', ' ', $order['status'])) }}
                            </span>
                        </td>                        
                        <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d-m-Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('vendor.product.details', ['product_id' => $order['product_id'], 'order_id' => $order['order_id']]) }}" class="btn btn-sm btn-info">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
  {{-- Vuexy DataTables Scripts --}}
  <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive-bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/jszip/jszip.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/pdfmake/pdfmake.js') }}"></script>
  <script src="{{ asset('assets/vendor/libs/pdfmake/vfs_fonts.js') }}"></script>

  {{-- DataTables Init --}}
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
