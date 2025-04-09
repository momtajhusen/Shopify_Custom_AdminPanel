@extends('AdminPanel.admin-layout')

@section('styles')
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
  <style>
    .image-preview-modal img {
      max-width: 100%;
      max-height: 500px;
      object-fit: contain;
    }
  </style>
@endsection

@section('content')
@php
  $hasAssignments = collect($order['products'])->contains(function ($product) {
      return isset($product['vendor_id']) && !empty($product['vendor_id']);
  });
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h5>Order #{{ $order['order_number'] }}</h5>
      <p class="mb-0">{{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y h:i A') }}</p>
    </div>
  </div>

  {{-- Product Table --}}
  <div class="card mb-4">
    <div class="card-body table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Image</th>
            <th>Product Id</th>
            <th>Product</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Assign Vendor</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($order['products'] as $product)
            <tr>
              <td>
                @if($product['image'])
                  <img src="{{ $product['image'] }}" alt="Image" class="img-thumbnail product-thumbnail" style="width: 50px; cursor: pointer; border-radius:5px" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="{{ $product['image'] }}">
                @else
                  <span class="text-muted">N/A</span>
                @endif
              </td>
              <td>#{{ $order['order_number'] }}-{{ $loop->iteration }}</td>
              <td class="product-name">{{ $product['name'] }}</td>
              <td>₹{{ number_format($product['price'], 2) }}</td>
              <td>{{ $product['quantity'] }}</td>
              <td>₹{{ number_format($product['price'] * $product['quantity'], 2) }}</td>
              <td>
                <select class="form-select vendor-select" data-product-id="{{ $product['product_id'] }}">
                  <option value="">-- Select Vendor --</option>
                  @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ (isset($product['vendor_id']) && $product['vendor_id'] == $vendor->id) ? 'selected' : '' }}>
                      {{ $vendor->name }} ({{ $vendor->vendor_code ?? 'N/A' }})
                    </option>
                  @endforeach
                </select>
              </td>
              <td>
                <button 
                  class="btn btn-sm {{ isset($product['vendor_id']) ? 'btn-warning update-btn' : 'btn-primary assign-btn' }}" 
                  data-product-id="{{ $product['product_id'] }}"
                  data-order-id="{{ $order['id'] }}"
                >
                  {{ isset($product['vendor_id']) ? 'Update' : 'Assign' }}
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="row align-items-center mt-4">
        <div class="col-md-6">
          <p><strong>Subtotal:</strong> ₹{{ number_format($order['subtotal'], 2) }}</p>
          <p><strong>Discount:</strong> ₹{{ number_format($order['discount'], 2) }}</p>
          <p><strong>Tax:</strong> ₹{{ number_format($order['tax'], 2) }}</p>
          <h5><strong>Total:</strong> ₹{{ number_format($order['total_amount'], 2) }}</h5>
        </div>
      </div>
    </div>
  </div>

  {{-- Customer Info --}}
  <div class="row">
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header"><h5>Customer Details</h5></div>
        <div class="card-body">
          <p><strong>Name:</strong> {{ $order['customer_name'] }}</p>
          <p><strong>Email:</strong> {{ $order['email'] }}</p>
          <p><strong>Phone:</strong> {{ $order['phone'] }}</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header"><h5>Shipping Address</h5></div>
        <div class="card-body">
          <p>{{ $order['address'] }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Image Preview Modal --}}
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content image-preview-modal">
      <div class="modal-header">
        <h5 class="modal-title">Product Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex justify-content-center align-items-center">
        <img src="" alt="Full Size" id="previewImage" class="img-fluid rounded" style="max-width: 90%; max-height: 80vh; object-fit: contain;">
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    $(document).ready(function () {
      // Assign or Update vendor
      $('.assign-btn, .update-btn').click(function () {
        let btn = $(this);
        let productId = btn.data('product-id');
        let orderId = btn.data('order-id');
        let vendorId = $(`.vendor-select[data-product-id="${productId}"]`).val();
        let productName = btn.closest('tr').find('.product-name').text();
        let actionType = btn.hasClass('assign-btn') ? 'Assign' : 'Update';

        if (!vendorId) {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a vendor before proceeding.'
          });
          return;
        }

        Swal.fire({
          icon: 'question',
          title: `${actionType} Vendor`,
          text: `Are you sure you want to ${actionType.toLowerCase()} vendor for "${productName}"?`,
          showCancelButton: true,
          confirmButtonText: `Yes, ${actionType}`,
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            btn.prop('disabled', true).text(`${actionType}ing...`);

            $.ajax({
              url: "{{ route('orders.assignVendorAjax', $order['id']) }}",
              type: "POST",
              data: {
                _token: "{{ csrf_token() }}",
                vendor_assignments: {
                  [productId]: { vendor_id: vendorId }
                }
              },
              success: function () {
                Swal.fire({
                  icon: 'success',
                  title: 'Success!',
                  text: `${actionType}ed vendor for "${productName}" successfully.`,
                  background: '#1e1e2f',
                  color: '#fff'
                });

                btn.removeClass('btn-primary assign-btn')
                   .addClass('btn-warning update-btn')
                   .text('Update')
                   .prop('disabled', false);
              },
              error: function (err) {
                Swal.fire({
                  icon: 'error',
                  title: 'Oops!',
                  text: err.responseJSON?.message || 'Something went wrong.',
                  background: '#1e1e2f',
                  color: '#fff'
                });
                btn.prop('disabled', false).text(actionType);
              }
            });
          }
        });
      });

      // Show image in modal
      $('.product-thumbnail').click(function () {
        let imageUrl = $(this).data('image');
        $('#previewImage').attr('src', imageUrl);
      });
    });
  </script>
@endsection