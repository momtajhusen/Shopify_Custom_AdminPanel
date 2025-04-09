@extends('AdminPanel.admin-layout')

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />

<style>
  #reportTable th, #reportTable td {
    white-space: nowrap !important;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-4">Assigned Product Report</h4>

  <div class="card">
    <div class="card-datatable table-responsive" style="overflow-x: auto;">
      <table id="reportTable" class="table table-striped table-bordered nowrap w-100">
        <thead>
          <tr>
            <th>#</th>
            <th>Order ID</th>
            <th>Product Image</th>
            <th>Product Name</th>
            <th>Vendor</th>
            <th>Order Price</th>
            <th>Quantity</th>
            <th>Vendor Price</th>
            <th>Margin</th>
            <th>Status</th>
            <th>Assigned At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
            @php
              $totalOrderPrice = 0;
              $totalVendorPrice = 0;
              $totalMargin = 0;
            @endphp
          
            @foreach($data as $index => $row)
            @php
              $totalOrderPrice += $row['order_price'];
              $totalVendorPrice += $row['vendor_price'];
              $totalMargin += $row['margin'];
            @endphp
            <tr>
              <td>{{ $index + 1 }}</td>
              <td>{{ $row['order_id'] }}</td>
              <td>
                @if($row['product_img'])
                  <img src="{{ $row['product_img'] }}" style="width:50px; border-radius: 4px;">
                @else
                  N/A
                @endif
              </td>
              <td>{{ $row['product_title'] }}</td>
              <td>{{ $row['vendor_name'] }}</td>
              <td>₹{{ number_format($row['order_price'], 2) }}</td>
              <td>{{ $row['quantity'] }}</td>
              <td>₹{{ number_format($row['vendor_price'], 2) }}</td>
              <td>
                ₹{{ number_format($row['margin'], 2) }}
                @if($row['margin'] < 0)
                  <span class="text-danger ms-1">Loss</span>
                @else
                  <span class="text-success ms-1">Profit</span>
                @endif
              </td>
              <td>
                @php
                    $status = $row['status'];
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
                <span class="badge bg-{{ $badgeClass }}">
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                  </span>
              </td>
              <td>{{ $row['assigned_at'] }}</td>
              <td>
                <a href="{{ route('admin.vendor.assigned.product.details', ['order_id' => $row['order_id'], 'product_id' => $row['product_id']]) }}" class="btn btn-sm btn-info">
                  View Details
                </a>
              </td>
              
            </tr>
            @endforeach
          </tbody>
          
          <tfoot>
            <tr style="font-weight: bold;">
              <td colspan="5" class="text-end">Total</td>
              <td>₹{{ number_format($totalOrderPrice, 2) }}</td>
              <td>₹{{ number_format($totalVendorPrice, 2) }}</td>
              <td>₹{{ number_format($totalMargin, 2) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
  $(document).ready(function () {
    $('#reportTable').DataTable({
      scrollX: true,
      pageLength: 25
    });
  });
</script>
@endsection