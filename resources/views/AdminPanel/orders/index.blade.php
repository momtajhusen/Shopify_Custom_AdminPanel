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
    <h5 class="pb-2 mb-4">Shopify Orders</h5>

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
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Products / Assigned</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Ordered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $key => $order)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>#{{ $order['order_number'] ?? 'N/A' }}</td>
                            <td>{{ $order['customer_name'] ?? 'N/A' }}</td>
                            <td>{{ $order['total_products'] ?? 0 }} / {{ $order['assigned_products'] ?? 0 }}</td>
                            <td>₹{{ number_format((float) $order['total_amount'], 2) }}</td>
                            <td>
                                @php
                                    $status = $order['status'] ?? 'pending';
                                    $badgeClass = match($status) {
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
                                <span class="badge bg-{{ $badgeClass }}" 
                                      data-bs-toggle="tooltip" 
                                      data-bs-placement="top" 
                                      title="Status: {{ ucfirst($status) }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>                            
                            <td>{{ \Carbon\Carbon::parse($order['created_at'])->format('d-m-Y h:i A') }}</td>
                            <td>
                                <a href="{{ route('orders.show', $order['id']) }}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No orders found.</td>
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
