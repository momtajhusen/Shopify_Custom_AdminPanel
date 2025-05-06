@extends('AdminPanel.admin-layout')

@section('styles')
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
  <style>
    /* Ensure product images in the modal do not overflow */
    .image-preview-modal img {
      max-width: 100%;
      max-height: 500px;
      object-fit: contain;
    }
  </style>
@endsection

@section('content')
@php
  use Carbon\Carbon;
  $products = $order['products'];
  // detect if any product already has a vendor
  $anyAssigned = collect($products)->contains(fn($p) => isset($p['vendor_id']));
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Order Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h5>Order #{{ $order['order_number'] }}</h5>
      <p class="mb-0">{{ Carbon::parse($order['created_at'])->format('M d, Y h:i A') }}</p>
    </div>
  </div>

  <!-- Products Table -->
  <div class="card mb-4">
    <div class="card-body table-responsive p-0">
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th>Image</th>
            <th>Product&nbsp;ID</th>
            <th>Product</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Assign&nbsp;Vendor</th>
            <th>Assign&nbsp;Price</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @php
            $allUnassigned = collect($products)->every(fn($p) => empty($p['vendor_id']));
          @endphp

          @foreach($products as $product)
          <tr>
            <td>
              @if($product['image'])
                <img src="{{ $product['image'] }}"
                     class="img-thumbnail product-thumbnail"
                     style="width:50px;cursor:pointer;border-radius:5px"
                     data-bs-toggle="modal"
                     data-bs-target="#imageModal"
                     data-image="{{ $product['image'] }}" />
              @else
                <span class="text-muted">N/A</span>
              @endif
            </td>
            <td>#{{ $order['order_number'] }}-{{ $loop->iteration }}</td>
            <td class="product-name">{{ $product['name'] }}</td>
            <td>₹{{ number_format($product['price'],2) }}</td>
            <td>{{ $product['quantity'] }}</td>
            <td>₹{{ number_format($product['price'] * $product['quantity'],2) }}</td>

            <!-- Vendor select -->
            <td>
              <select class="form-select vendor-select" data-product-id="{{ $product['product_id'] }}">
                <option value="">-- Select Vendor --</option>
                @foreach($vendors as $vendor)
                  <option value="{{ $vendor->id }}"
                    {{ (isset($product['vendor_id']) && $product['vendor_id']==$vendor->id) ? 'selected':'' }}>
                    {{ $vendor->name }} ({{ $vendor->vendor_code ?? 'N/A' }})
                  </option>
                @endforeach
              </select>
            </td>

            <!-- Vendor price -->
            <td>
              <input type="number"
                     class="form-control assign-price"
                     data-product-id="{{ $product['product_id'] }}"
                     value="{{ $product['vendor_price'] ?? '' }}"
                     placeholder="price" min="0" />
            </td>

            <!-- Action or status badge -->
            <td>
              @php
                $finalStatuses = ['shipped','in_transit','delivered'];
                $statusColors = [
                  'shipped'=>'bg-primary',
                  'in_transit'=>'bg-warning',
                  'delivered'=>'bg-success'
                ];
              @endphp

              @if(in_array($product['status'],$finalStatuses))
                <span class="badge {{ $statusColors[$product['status']] ?? 'bg-secondary' }}">
                  {{ ucfirst(str_replace('_',' ',$product['status'])) }}
                </span>
              @else
                <button class="btn btn-sm {{ isset($product['vendor_id'])?'btn-warning update-btn':'btn-primary assign-btn' }}"
                        data-product-id="{{ $product['product_id'] }}"
                        data-order-id="{{ $order['id'] }}">
                  {{ isset($product['vendor_id'])?'Update':'Assign' }}
                </button>
              @endif
            </td>
          </tr>
          @endforeach

          @if($allUnassigned)
          <tr>
            <td colspan="9" class="text-end">
              <button id="assignAllBtn"
                      class="btn btn-sm btn-primary"
                      data-url="{{ route('orders.assignVendorAjax',$order['id']) }}">
                ASSIGN ALL
              </button>
            </td>
          </tr>
          @endif

        </tbody>
      </table>
    </div>
  </div>

  <!-- Order Summary & Assigned Vendor Details -->
  <div class="row mb-4">
    <!-- Summary -->
    <div class="col-md-6">
      <div class="card p-3">
        <p><strong>Subtotal:</strong> ₹{{ number_format($order['subtotal'],2) }}</p>
        <p><strong>Discount:</strong> ₹{{ number_format($order['discount'],2) }}</p>
        <p><strong>Tax:</strong> ₹{{ number_format($order['tax'],2) }}</p>
        <h5><strong>Total:</strong> ₹{{ number_format($order['total_amount'],2) }}</h5>
      </div>
    </div>

    <!-- Assigned Vendor Details -->
    <div class="col-md-6">
      <h6>Assigned Vendor Details</h6>
      @foreach($products as $product)
        @if(isset($product['vendor_id']))
          <div class="card mb-2">
            <div class="card-body p-2">
              <div class="d-flex justify-content-between">
                <h6 class="card-title">{{ $product['name'] }}</h6>
                <span class="card-text">
                  <strong>Status:</strong>
                  {{ ucfirst(str_replace('_',' ',$product['status'])) }}
                </span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="card-text">
                  <strong>Vendor:</strong>
                  @foreach($vendors as $vendor)
                    @if($vendor->id==$product['vendor_id'])
                      {{ $vendor->name }} ({{ $vendor->vendor_code ?? 'N/A' }})
                    @endif
                  @endforeach
                </span>
                @if(
                  isset($product['assignment_id']) &&
                  isset($product['vendor_price']) &&
                  in_array($product['status'],['shipped','in_transit'])
                )
                  <form id="deliveredForm_{{ $product['assignment_id'] }}"
                        method="POST"
                        action="{{ route('updateStatus',$product['assignment_id']) }}"
                        class="d-inline">
                    @csrf
                    <input type="hidden" name="status" value="delivered">
                    <button type="button"
                            class="btn btn-success btn-sm"
                            onclick="confirmDelivered({{ $product['assignment_id'] }})">
                      Mark as Delivered
                    </button>
                  </form>
                @endif
              </div>
              @if(isset($product['vendor_price']))
                <div class="mt-2">
                  <strong>Vendor Price:</strong>
                  ₹{{ number_format($product['vendor_price'],2) }}
                </div>
              @endif
            </div>
          </div>
        @endif
      @endforeach
    </div>
  </div>

  <!-- Customer & Shipping Info -->
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header"><h5>Customer Details</h5></div>
        <div class="card-body">
          <p><strong>Name:</strong> {{ $order['customer_name'] }}</p>
          <p><strong>Email:</strong> {{ $order['email'] }}</p>
          <p><strong>Phone:</strong> {{ $order['phone'] }}</p>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header"><h5>Shipping Address</h5></div>
        <div class="card-body">
          <p>{{ $order['address'] }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content image-preview-modal">
      <div class="modal-header">
        <h5 class="modal-title">Product Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body d-flex justify-content-center align-items-center">
        <img src="" alt="Full Size" id="previewImage" class="img-fluid rounded"
             style="max-width:90%;max-height:80vh;object-fit:contain;">
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(function () {

    // Handle single assign/update
    $(document).on('click', '.assign-btn, .update-btn', function () {
      const pid = $(this).data('product-id');
      const orderId = $(this).data('order-id');
      const actionLabel = $(this).hasClass('assign-btn') ? 'Assign' : 'Update';
      assignVendor(pid, orderId, actionLabel);
    });

    // Handle Assign All
    $(document).on('click', '#assignAllBtn', function () {
      const orderId = "{{ $order['id'] }}";
      const url = $(this).data('url');
      assignAllVendors(orderId, url);
    });

    // Image Preview
    $(document).on('click', '.product-thumbnail', function () {
      $('#previewImage').attr('src', $(this).data('image'));
    });

  });

  function assignVendor(pid, orderId, label) {
    const vid = $(`.vendor-select[data-product-id="${pid}"]`).val();
    const price = $(`.assign-price[data-product-id="${pid}"]`).val();
    const $btn = $(`.assign-btn[data-product-id="${pid}"], .update-btn[data-product-id="${pid}"]`);

    if (!vid) return Swal.fire('Error', 'Please select a vendor.', 'error');
    if (!price) return Swal.fire('Error', 'Please enter a price.', 'error');

    Swal.fire({
      icon: 'question',
      title: `${label} Vendor?`,
      showCancelButton: true,
      confirmButtonText: `Yes, ${label}`
    }).then(result => {
      if (!result.isConfirmed) return;

      $btn.prop('disabled', true).text(`${label}ing…`);

      $.post("{{ route('orders.assignVendorAjax', $order['id']) }}", {
        _token: "{{ csrf_token() }}",
        vendor_assignments: { [pid]: { vendor_id: vid, vendor_price: price } }
      }).done(res => showVendorResponse(res, $btn, label))
        .fail(err => {
          Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
          $btn.prop('disabled', false).text(label);
        });
    });
  }

  function assignAllVendors(orderId, url) {
    const assigns = {}, errors = [];
    const $btn = $('#assignAllBtn');
    const label = 'ASSIGN ALL';

    $('.vendor-select').each(function () {
      const pid = $(this).data('product-id');
      const vid = $(this).val();
      const price = $(`.assign-price[data-product-id="${pid}"]`).val();
      if (!vid) errors.push(`Missing vendor for product ${pid}`);
      if (!price) errors.push(`Missing price for product ${pid}`);
      assigns[pid] = { vendor_id: vid, vendor_price: price };
    });

    if (errors.length) {
      return Swal.fire('Error', 'Please complete all fields:<br>' + errors.join('<br>'), 'error');
    }

    Swal.fire({
      icon: 'question',
      title: 'Assign All Vendors?',
      showCancelButton: true,
      confirmButtonText: 'Yes, Assign'
    }).then(result => {
      if (!result.isConfirmed) return;

      $btn.prop('disabled', true).text('Assigning…');

      $.post(url, {
        _token: "{{ csrf_token() }}",
        vendor_assignments: assigns
      }).done(res => showVendorResponse(res, $btn, label))
        .fail(err => {
          Swal.fire('Error', err.responseJSON?.message || 'Something went wrong.', 'error');
          $btn.prop('disabled', false).text(label);
        });
    });
  }

  function showVendorResponse(res, $btn, label) {
    if (res.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: res.message,
        toast: true,
        position: 'top-end',
        timer: 2000,
        showConfirmButton: false
      }).then(() => location.reload());
    } else {
      Swal.fire({
        icon: 'warning',
        title: 'Partial Success',
        html: res.message + '<ul>' + (res.errors || []).map(e => `<li>${e}</li>`).join('') + '</ul>',
        confirmButtonText: 'OK'
      });
      $btn.prop('disabled', false).text(label);
    }
  }

  // Confirm Delivered
  function confirmDelivered(id) {
    Swal.fire({
      icon: 'question',
      title: 'Mark as Delivered?',
      showCancelButton: true,
      confirmButtonText: 'Yes, Deliver'
    }).then(result => {
      if (result.isConfirmed) {
        Swal.fire({
          icon: 'success',
          title: 'Delivered!',
          timer: 1000,
          showConfirmButton: false
        }).then(() => {
          document.getElementById(`deliveredForm_${id}`).submit();
        });
      }
    });
  }
</script>
 

@endsection
