@extends('AdminPanel.admin-layout')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
<style>
  .timeline .timeline-item { margin-bottom: .75rem!important }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h5 class="pb-2 mb-4">Product &amp; Order Details</h5>

  <div class="card mb-4">
    <div class="card-body">
      <div class="row">

        {{-- Product Image --}}
        <div class="col-md-4 mb-2">
          <img src="{{ $productImageUrl }}" alt="Product"
               style="width:100%;max-width:200px;border-radius:10px;">
        </div>

        {{-- Vendor Actions --}}
        <div class="col-md-4">
          <h5>{{ $lineItem['title'] }}</h5>
          <p><strong>Quantity:</strong> {{ $lineItem['quantity'] ?? '-' }}</p>

          {{-- Price Input --}}
          @if(!$assignment->vendor_price && $assignment->status === 'assigned')
            <form method="POST" action="{{ route('submitPrice', $assignment->id) }}"
                  onsubmit="return handlePriceSubmit(event)">
              @csrf
              <div class="mb-3">
                <label class="form-label" for="vendor_price">Enter your manufacturer price</label>
                <input type="number" name="vendor_price" id="vendor_price"
                       class="form-control"
                       value="{{ old('vendor_price', $previousPrice) }}" required>
              </div>
              <button class="btn btn-primary w-100">Accept &amp; Submit Price</button>
            </form>
          @else
            <div class="mb-3">
              <label class="form-label">Price</label>
              <input type="number" value="{{ $assignment->vendor_price }}"
                     class="form-control" disabled>
            </div>
          @endif

          {{-- Accept/Reject --}}
          @if($assignment->status === 'assigned' && $assignment->vendor_price)
            <form class="mb-1" method="POST" action="{{ route('acceptPrice', $assignment->id) }}">
              @csrf
              <button class="btn btn-success w-100">Accept</button>
            </form>

            <form method="POST" action="{{ route('rejectPrice', $assignment->id) }}"
                  onsubmit="return confirmReject(event)">
              @csrf
              <button class="btn btn-danger w-100">Reject</button>
            </form>
          @endif

          {{-- Status Dropdown --}}
          @php
            $statusesFull = [
              'assigned'   => 'secondary',
              'accepted'   => 'info',
              'shipped'    => 'dark',
              'in_transit' => 'orange',
              'delivered'  => 'success',
              'rejected'   => 'danger',
            ];
            $statusKeys   = array_keys($statusesFull);
            $currentIndex = array_search($assignment->status, $statusKeys);
            // determine next statuses, excluding "rejected" except from "assigned"
            $nextStatuses = array_slice($statusKeys, $currentIndex + 1);
            if ($assignment->status !== 'assigned') {
              $nextStatuses = array_filter($nextStatuses, fn($s) => $s !== 'rejected');
            }
          @endphp

          @if(! in_array($assignment->status, ['assigned','delivered','rejected']))
            <form id="statusUpdateForm">@csrf
              <div class="my-4">
                <label class="form-label" for="statusSelect">Update Status</label>
                <select name="status" id="statusSelect" class="form-select"
                        onchange="handleStatusChangeModal('{{ $assignment->status }}')">
                  <option value="">-- Select Status --</option>
                  @foreach($nextStatuses as $s)
                    <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                  @endforeach
                </select>
              </div>
              <div id="statusBtnWrapper" class="mb-6" style="display:none">
                <button id="statusUpdateBtn" type="button"
                        class="btn btn-primary d-grid w-100">Update</button>
              </div>
            </form>
          @endif

          {{-- Status Message --}}
          @php
            if ($assignment->status === 'assigned') {
              $msg = $assignment->vendor_price
                   ? ['info', 'Admin has offered a price. Please accept or reject below.']
                   : ['secondary', 'You have been assigned this product. Please enter your price to accept the order.'];
            } else {
              $lookup = [
                'accepted'   => ['info',    'Price accepted. Pack the item then mark "Shipped" with AWB.'],
                'shipped'    => ['dark',    'Product shipped. Next step: "In Transit".'],
                'in_transit' => ['orange',  'Parcel in transit. After customer receives, mark "Delivered".'],
                'delivered'  => ['success', 'Delivered successfully. No further action needed.'],
                'rejected'   => ['danger',  'You rejected the offered price. Wait for admin to resend.'],
              ];
              $msg = $lookup[$assignment->status] ?? ['secondary',''];
            }
          @endphp

          <div class="alert alert-{{ $msg[0] }} mt-2">
            <strong>Status:</strong> {{ $msg[1] }}
          </div>
        </div>

        {{-- Timeline --}}
        <div class="col-md-4 text-center">
          <div class="card h-100">
            <h5 class="card-header">Status</h5>
            <div class="card-body" id="statusTimeline">
              <ul class="timeline mb-0">
                @php
                  if ($assignment->status === 'rejected') {
                    $timelineStatuses = ['assigned','rejected'];
                  } else {
                    $timelineStatuses = array_filter($statusKeys, fn($s) => $s !== 'rejected');
                  }
                  $currentIndex = array_search($assignment->status, $statusKeys);
                @endphp

                @foreach($timelineStatuses as $st)
                  @php
                    $clr = $statusesFull[$st];
                    $idx = array_search($st, $statusKeys);
                  @endphp
                  <li class="timeline-item timeline-item-transparent">
                    <span class="timeline-point {{ $idx <= $currentIndex ? 'bg-'.$clr : 'bg-light' }}"></span>
                    <div class="timeline-event mb-0 pb-0">
                      <h6 class="mb-0 text-capitalize {{ $idx <= $currentIndex ? '' : 'text-muted' }}">
                        {{ str_replace('_',' ',$st) }}
                      </h6>
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

{{-- AWB Modal --}}
<div class="modal fade" id="awbModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('submitAwb', $assignment->id) }}"
          onsubmit="return handleAwbSubmit(event)">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Enter AWB Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="awb_number">AWB Number</label>
            <input name="awb_number" id="awb_number" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="courier_company">Courier Company</label>
            <input name="courier_company" id="courier_company" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="dispatch_date">Dispatch Date</label>
            <input type="date" name="dispatch_date" id="dispatch_date"
                   class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="tracking_url">Tracking URL</label>
            <input type="url" name="tracking_url" id="tracking_url"
                   class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">Submit AWB &amp; Update</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Show SweetAlert after redirect --}}
@if(session('success'))
<script>
  Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    timer: 2000,
    showConfirmButton: false
  });
</script>
@endif

@if(session('error'))
<script>
  Swal.fire({
    icon: 'error',
    title: '{{ session('error') }}',
    timer: 3000,
    showConfirmButton: true
  });
</script>
@endif

<script>
/* ---------- PRICE SUBMIT ---------- */
function handlePriceSubmit(e){
  e.preventDefault();
  Swal.fire({icon:'success',title:'Price Submitted!',timer:2000,showConfirmButton:false})
       .then(()=>e.target.submit());
  return false;
}

/* ---------- AWB SUBMIT ---------- */
function handleAwbSubmit(e){
  e.preventDefault();
  Swal.fire({icon:'success',title:'Submitting AWB...',timer:1000,showConfirmButton:false})
       .then(()=>e.target.submit());
  return false;
}

/* ---------- STATUS CHANGE ---------- */
function handleStatusChangeModal(current){
  const sel = document.getElementById('statusSelect');
  const wrap= document.getElementById('statusBtnWrapper');
  const btn = document.getElementById('statusUpdateBtn');
  if(!sel.value || sel.value === current){ wrap.style.display='none'; return; }
  wrap.style.display='block';

  if(sel.value==='shipped'){
    btn.innerText='Fill AWB Details';
    btn.onclick = ()=>new bootstrap.Modal(document.getElementById('awbModal')).show();
  }else{
    btn.innerText='Update';
    btn.onclick = updateStatusAjax;
  }
}

/* ---------- STATUS AJAX ---------- */
function updateStatusAjax(){
  const status = document.getElementById('statusSelect').value;
  fetch(`/order/{{ $assignment->id }}/update-status`,{
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN':document.querySelector('input[name="_token"]').value
    },
    body:JSON.stringify({status})
  })
  .then(r=>r.json())
  .then(d=>{
    if(d.success){
      Swal.fire({icon:'success',title:'Status Updated!',timer:2000,showConfirmButton:false})
          .then(()=>location.reload());
    }
  })
  .catch(()=>Swal.fire('Error','Something went wrong!','error'));
}

/* ---------- REJECT CONFIRM ---------- */
function confirmReject(e){
  e.preventDefault();
  Swal.fire({
    icon:'warning',title:'Reject price?',text:'Are you sure you want to reject this price?',
    showCancelButton:true,confirmButtonText:'Yes, Reject'
  }).then(r=>{ if(r.isConfirmed) e.target.submit(); });
  return false;
}
</script>
@endsection
