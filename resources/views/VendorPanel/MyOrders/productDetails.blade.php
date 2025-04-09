@extends('AdminPanel.admin-layout')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
<style>
  .timeline .timeline-item {
    margin-bottom: 0.75rem !important;
  }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h5 class="pb-2 mb-4">Product & Order Details</h5>

  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <!-- Product Image -->
        <div class="col-md-4 mb-2">
          <img src="{{ $productImageUrl }}" alt="Product" style="width: 100%; max-width: 200px; border-radius: 10px;">
        </div>

        <!-- Product & Vendor Controls -->
        <div class="col-md-4">
          <h5>{{ $lineItem['title'] }}</h5>
          <p><strong>Quantity:</strong> {{ $lineItem['quantity'] ?? '-' }}</p>

          <!-- Vendor Price -->
          @if (!$assignment->vendor_price && $assignment->status === 'assigned')
          <form method="POST" action="{{ route('submitPrice', ['assignment_id' => $assignment->id]) }}" onsubmit="return handlePriceSubmit(event)">
              @csrf
              <div class="mb-3">
                  <label for="vendor_price" class="form-label">Enter your manufacturer price</label>
                  <input type="number" name="vendor_price" id="vendor_price" class="form-control" 
                        value="{{ old('vendor_price', $previousPrice) }}" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                  Accept & Submit Price
              </button>
          </form>
          @else
          <div class="mb-3">
              <label class="form-label">Your Price</label>
              <input type="number" value="{{ $assignment->vendor_price }}" class="form-control" disabled>
          </div>
          @endif


          <!-- Status Dropdown -->
          @php
            $statusesFull = [
              'assigned' => 'secondary',
              'accepted' => 'info',
              'in_process' => 'warning',
              'ready' => 'primary',
              'shipped' => 'dark',
              'in_transit' => 'orange',
              'delivered' => 'success',
            ];
            $statusKeys = array_keys($statusesFull);
            $currentIndex = array_search($assignment->status, $statusKeys);
          @endphp

          @if ($assignment->status !== 'assigned' && $assignment->status !== 'delivered')
          <form id="statusUpdateForm">
            @csrf
            <div class="my-4">
              <label for="statusSelect" class="form-label">Update Status</label>
              <select name="status" id="statusSelect" class="form-select" onchange="handleStatusChangeModal('{{ $assignment->status }}')">
                <option value="">-- Select Status --</option>
                @foreach(array_slice($statusKeys, $currentIndex + 1) as $status)
                <option value="{{ $status }}">
                  {{ ucwords(str_replace('_', ' ', $status)) }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="mb-6" id="statusBtnWrapper" style="display: none;">
              <button class="btn btn-primary d-grid w-100" id="statusUpdateBtn" type="button">Update</button>
            </div>
          </form>
          @endif
                    <!-- Status Message -->
                    <div class="mt-2">
                      @php 
                      $statusMessages = [
                        'assigned' => [
                          'color' => 'secondary',
                          'text' => 'You have been assigned this product. Please enter your price to accept the order.'
                        ],
                        'accepted' => [
                          'color' => 'info',
                          'text' => 'You accepted the order. Now start preparing the product and update the status when it\'s in process.'
                        ],
                        'in_process' => [
                          'color' => 'warning',
                          'text' => 'Product is being prepared. Once it\'s ready, update the status to "Ready".'
                        ],
                        'ready' => [
                          'color' => 'primary',
                          'text' => 'Product is ready for dispatch. Please update the status to "Shipped" when it is dispatched.'
                        ],
                        'shipped' => [
                          'color' => 'dark',
                          'text' => 'Product has been shipped. Provide tracking details and update to "In Transit".'
                        ],
                        'in_transit' => [
                          'color' => 'orange',
                          'text' => 'Product is in transit. Once the customer receives the product, please update the status to "Delivered".'
                        ],
                        'delivered' => [
                          'color' => 'success',
                          'text' => 'Product has been delivered successfully. No further action is needed. Thank you!'
                        ],
                      ];
                    @endphp                    
                      <div class="alert alert-{{ $statusMessages[$assignment->status]['color'] }}">
                        <strong>Status:</strong> {{ $statusMessages[$assignment->status]['text'] }}
                      </div>
                    </div>
          
        </div>


        <!-- Status Timeline -->
        <div class="col-md-4 text-center">
          <div class="card h-100">
            <h5 class="card-header">Status</h5>
            <div class="card-body" id="statusTimeline">
              <ul class="timeline mb-0">
                @foreach($statusesFull as $status => $color)
                <li class="timeline-item timeline-item-transparent">
                  <span class="timeline-point {{ array_search($status, $statusKeys) <= $currentIndex ? 'bg-' . $color : 'bg-light' }}"></span>
                  <div class="timeline-event mb-0 pb-0">
                    <div class="timeline-header mb-0">
                      <h6 class="mb-0 text-capitalize {{ array_search($status, $statusKeys) <= $currentIndex ? '' : 'text-muted' }}">
                        {{ str_replace('_', ' ', $status) }}
                      </h6>
                    </div>
                  </div>
                </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- AWB Modal for Shipped -->
<div class="modal fade" id="awbModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('submitAwb', ['assignment_id' => $assignment->id]) }}" onsubmit="return handleAwbSubmit(event)">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Enter AWB Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="awb_number" class="form-label">AWB Number</label>
            <input type="text" name="awb_number" class="form-control" id="awb_number" required>
          </div>
          <div class="mb-3">
            <label for="courier_company" class="form-label">Courier Company</label>
            <input type="text" name="courier_company" class="form-control" id="courier_company" required>
          </div>
          <div class="mb-3">
            <label for="dispatch_date" class="form-label">Dispatch Date</label>
            <input type="date" name="dispatch_date" class="form-control" id="dispatch_date" required>
          </div>
          <div class="mb-3">
            <label for="tracking_url" class="form-label">Tracking URL</label>
            <input type="url" name="tracking_url" class="form-control" id="tracking_url" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit AWB & Update</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function handlePriceSubmit(event) {
    event.preventDefault();
    Swal.fire({
      icon: 'success',
      title: 'Price Submitted!',
      text: 'Your vendor price has been accepted.',
      timer: 2000,
      showConfirmButton: false
    }).then(() => {
      event.target.submit();
    });
    return false;
  }

  function handleAwbSubmit(event) {
  event.preventDefault();  // Prevent form submission immediately

  // Show SweetAlert with success message
  Swal.fire({
    icon: 'success',
    title: 'AWB Submitted!',
    text: 'Shipment tracking information updated.',
    timer: 2000,  // SweetAlert will disappear after 2 seconds
    showConfirmButton: false
  }).then(() => {
    // After the SweetAlert disappears, submit the form
    event.target.submit();  // Submit the form after SweetAlert closes
  });

  return false;  // Prevent immediate form submission
}



  function handleStatusChangeModal(currentStatus) {
    const status = document.getElementById('statusSelect').value;
    const btnWrapper = document.getElementById('statusBtnWrapper');
    const btn = document.getElementById('statusUpdateBtn');

    if (status === "" || status === currentStatus) {
      btnWrapper.style.display = 'none';
      return;
    }

    btnWrapper.style.display = 'block';

    if (status === "shipped") {
      btn.innerText = "Fill AWB Details";
      btn.type = "button";
      btn.onclick = () => new bootstrap.Modal(document.getElementById('awbModal')).show();
    } else {
      btn.innerText = "Update";
      btn.type = "button";
      btn.onclick = updateStatusAjax;
    }
  }

  function updateStatusAjax() {
    const status = document.getElementById('statusSelect').value;
    const assignmentId = "{{ $assignment->id }}";
    const csrfToken = document.querySelector('input[name="_token"]').value;

    fetch(`/order/${assignmentId}/update-status`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ status })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Status Updated!',
          text: data.message,
          timer: 2000,
          showConfirmButton: false
        });

        location.reload();
      }
    })
    .catch(() => {
      Swal.fire('Error', 'Something went wrong!', 'error');
    });
  }
</script>
@endsection




