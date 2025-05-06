@extends('AdminPanel.admin-layout')

@section('styles')
<style>
  /* Ensure table can scroll inside card on small screens */
  .card .table-responsive {
    overflow-x: auto;
  }
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

@section('scripts')
<script>
  $(document).ready(function () {
    $('#getPackingSlipBtn').click(function () {
      let waybill = '19928210237252'; // Replace with dynamic

      $.ajax({
        url: '{{ url("/delhivery/packing-slip") }}',
        method: 'POST',
        data: {
          waybill: waybill,
          _token: '{{ csrf_token() }}'
        },
        beforeSend: function () {
          $('#getPackingSlipBtn').prop('disabled', true).text('Fetching...');
        },
        success: function (response) {
          if (response.packages_found && response.packages.length > 0) {
            const pkg = response.packages[0];

            $('#barcodeImgModal').attr('src', pkg.barcode || '');
            $('#oidBarcodeImgModal').attr('src', pkg.oid_barcode || '');
            $('#clientLogo').attr('src', pkg.cl_logo || '');
            $('#delhiveryLogo').attr('src', pkg.delhivery_logo || '');

            $('#oidText').text(pkg.oid || '');
            $('#customerName').text(pkg.name || '');
            $('#paymentType').text(pkg.pt || '');
            $('#amount').text(pkg.rs || '');
            $('#origin').text(pkg.origin || '');
            $('#destination').text(pkg.destination || '');
            $('#deliveryAddress').text(pkg.address || '');

            $('#packingSlipModal').modal('show');
          } else {
            alert('Packing slip not found.');
          }
        },
        error: function (xhr) {
          alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong.'));
        },
        complete: function () {
          $('#getPackingSlipBtn').prop('disabled', false).text('Get Packing Slip');
        }
      });
    });

    // 👉 1. Download only shipment barcode
    $('#downloadShipmentBarcode').on('click', function () {
      const barcodeImg = document.getElementById('barcodeImgModal');
      if (barcodeImg && barcodeImg.src) {
        const link = document.createElement('a');
        link.href = barcodeImg.src;
        link.download = 'shipment-barcode.png';
        link.click();
      } else {
        alert('Barcode image not found.');
      }
    });

    // 👉 2. Download full shipping label as image
    $('#downloadPackingSlip').on('click', function () {
      const element = document.getElementById('shippingLabelContent');
      html2canvas(element, { scale: 2 }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'shipping-label.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
      });
    });

  });
</script>
@endsection

@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- Page Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
      <div>
        <h5 class="mb-1">Assigned Product #{{ $data['order_id'] }}</h5>
        <p class="mb-0">Assigned On: {{ $data['assigned_at'] }}</p>
      </div>
    </div>

    <div class="row">
      
      {{-- Left Section --}}
      <div class="col-lg-8">

        {{-- Product Details --}}
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Product Details (Vendor to Admin Received)</h6>
            <span class="badge bg-label-info">
              {{ ucfirst(str_replace('_', ' ', $data['status'])) }}
            </span>
          </div>

          <div class="card-body">
            {{-- Product Table --}}
            <div class="table-responsive mt-4">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th></th>
                    <th>Product</th>
                    <th>Order Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><img src="{{ $data['product_img'] }}" width="50"></td>
                    <td>{{ $data['product_title'] }}</td>
                    <td>₹{{ number_format($data['order_price'], 2) }}</td>
                    <td>{{ $data['quantity'] }}</td>
                    <td>₹{{ number_format($data['order_price'] * $data['quantity'], 2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            {{-- Action Buttons and Price Summary --}}
            <div class="d-flex flex-column-reverse flex-md-row justify-content-end align-items-center mt-3">

              {{-- Price Summary --}}
              <div class="mb-2 mb-md-0">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                  <div class="d-flex justify-content-between">
                    <span class="me-2">Subtotal:</span>
                    <strong>₹{{ number_format($data['order_price'] * $data['quantity'], 2) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="me-2">Vendor Price:</span>
                    <strong>{{ $data['vendor_price'] ? '₹' . number_format($data['vendor_price'], 2) : 'N/A' }}</strong>
                  </div>
                </div>
              </div>

            </div>

          </div>
        </div>
 
        
        {{-- Order Progress Timeline --}}
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">Order Progress Timeline</h6>
          </div>

          <div class="card-body pt-2">
            <div class="accordion" id="statusAccordion">

              {{-- Vendor to Admin Timeline --}}
              <div class="accordion-item border">
                <h2 class="accordion-header" id="vendorTimelineHeading">
                  <button class="accordion-button d-flex justify-content-between align-items-center {{ strtolower($data['status']) == 'delivered' ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#vendorTimelineCollapse" aria-expanded="{{ strtolower($data['status']) != 'delivered' ? 'true' : 'false' }}" aria-controls="vendorTimelineCollapse">
                    <div class="flex-grow-1">Vendor Delivery to Admin (Receiving Status)</div>
                    <span class="badge bg-label-info ms-2">{{ ucfirst($data['status'] ?? 'N/A') }}</span>
                  </button>
                </h2>
                <div id="vendorTimelineCollapse" class="accordion-collapse collapse {{ strtolower($data['status']) != 'delivered' ? 'show' : '' }}" aria-labelledby="vendorTimelineHeading" data-bs-parent="#statusAccordion">
                  <div class="accordion-body">
                    @if(!empty($timeline))
                      <ul class="timeline mb-0 pt-3">
                        @foreach($timeline as $entry)
                          <li class="timeline-item timeline-item-transparent border-primary">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                              <div class="timeline-header">
                                <h6 class="mb-0">{{ $entry['label'] }}</h6>
                                @if($entry['time'])
                                  <small>{{ $entry['time'] }}</small>
                                @endif
                              </div>
                              <p class="mt-2 mb-0">{{ $entry['desc'] }}</p>
                            </div>
                          </li>
                        @endforeach
                      </ul>
                    @else
                      <div class="text-muted">No vendor to admin timeline available.</div>
                    @endif
                  </div>
                </div>
              </div>

              {{-- Admin to Customer Timeline --}}
              <div class="accordion-item border">
                <h2 class="accordion-header" id="adminTimelineHeading">
                  <button class="accordion-button d-flex justify-content-between align-items-center {{ strtolower($data['status']) != 'delivered' ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#adminTimelineCollapse" aria-expanded="{{ strtolower($data['status']) == 'delivered' ? 'true' : 'false' }}" aria-controls="adminTimelineCollapse">
                    <div class="flex-grow-1">Admin Shipment to Customer (Dispatch Status)</div>
                    @if(!empty($data['dispatch_status']))
                      <span class="badge bg-label-success ms-2">{{ ucfirst($data['dispatch_status']) }}</span>
                    @else
                      <span class="badge bg-label-secondary ms-2">Pending</span>
                    @endif
                  </button>
                </h2>
                <div id="adminTimelineCollapse" class="accordion-collapse collapse {{ strtolower($data['status']) == 'delivered' ? 'show' : '' }}" aria-labelledby="adminTimelineHeading" data-bs-parent="#statusAccordion">
                  <div class="accordion-body">

                    {{-- Check if dispatch available --}}
                    @if(!empty($data['dispatch_full_status']))
                      {{-- Latest Dispatch Status --}}
                      <ul class="timeline mb-0 pt-3">
                        <li class="timeline-item timeline-item-transparent border-success">
                          <span class="timeline-point timeline-point-success"></span>
                          <div class="timeline-event">
                            <div class="timeline-header">
                              <h6 class="mb-0">{{ $data['dispatch_full_status']['Status'] ?? 'Status Unknown' }}</h6>
                              @if(!empty($data['dispatch_full_status']['StatusDateTime']))
                                <small>{{ \Carbon\Carbon::parse($data['dispatch_full_status']['StatusDateTime'])->format('d-m-Y h:i A') }}</small>
                              @endif
                            </div>
                            <p class="mt-2 mb-1"><strong>Location:</strong> {{ $data['dispatch_full_status']['StatusLocation'] ?? '-' }}</p>
                            @if(!empty($data['dispatch_full_status']['Instructions']))
                              <p class="mb-0"><strong>Note:</strong> {{ $data['dispatch_full_status']['Instructions'] }}</p>
                            @endif
                          </div>
                        </li>
                      </ul>

                      {{-- Dispatch Tracking History --}}
                      @if(!empty($data['dispatch_tracking_history']))
                        <h6 class="text-muted mt-4">Dispatch Tracking History</h6>
                        <ul class="timeline mb-0 pt-3">
                          @foreach($data['dispatch_tracking_history'] as $track)
                            <li class="timeline-item timeline-item-transparent border-secondary">
                              <span class="timeline-point timeline-point-secondary"></span>
                              <div class="timeline-event">
                                <div class="timeline-header">
                                  <h6 class="mb-0">{{ $track['status'] ?? 'Unknown' }}</h6>
                                  @if(!empty($track['datetime']))
                                    <small>{{ \Carbon\Carbon::parse($track['datetime'])->format('d-m-Y h:i A') }}</small>
                                  @endif
                                </div>
                                <p class="mt-2 mb-1"><strong>Location:</strong> {{ $track['location'] ?? '-' }}</p>
                                @if(!empty($track['instruction']))
                                  <p class="mb-0"><strong>Note:</strong> {{ $track['instruction'] }}</p>
                                @endif
                              </div>
                            </li>
                          @endforeach
                        </ul>
                      @else
                        <div class="text-muted pt-2">No latest dispatch status available.</div>
                        <button type="button" class="btn btn-outline-primary" id="getPackingSlipBtn">
                          Print Packing Slip
                        </button>
                        {{-- <a href="{{ url('/download/packing-slip/19928210237230') }}" class="btn btn-primary" target="_blank">
                          Download Packing Slip (PDF)
                        </a>
                         --}}
                      @endif

                    @else
                      {{-- Dispatch NOT Available → Show Action Button here inside accordion-body --}}
                      <div class="text-center mt-3">
                        {{-- Action Button --}}
                        @if(empty($data['dispatch_awb']))
                          @if($data['status'] == 'delivered')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dispatchOrderModal">
                              Dispatch to Customer
                            </button>
                          @else
                            <button type="button" class="btn btn-secondary" disabled>
                              Waiting for Vendor Delivery
                            </button>
                          @endif
                        @elseif(strtolower($data['dispatch_status']) == 'delivered')
                          <button type="button" class="btn btn-success" disabled>
                            Delivered to Customer
                          </button>
                        @else
                          <button type="button" class="btn btn-warning" disabled>
                            Shipment In Progress ({{ $data['dispatch_status'] ?? 'Pending' }})
                          </button>
                        @endif
                      </div>
                    @endif

                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>





      </div>

      {{-- Right Section: Vendor Info + AWB Details --}}
      <div class="col-lg-4">

        {{-- Vendor Info --}}
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0">Vendor who Delivered</h6>
          </div>
          <div class="card-body">
            <p><strong>Name:</strong> {{ $data['vendor_name'] }}</p>
            <p><strong>Vendor ID:</strong> #{{ $data['vendor_id'] }}</p>
            <p><strong>Email:</strong> {{ $data['vendor_email'] }}</p>
            <p><strong>Phone:</strong> {{ $data['vendor_phone'] }}</p>
          </div>
        </div>

        {{-- AWB Details --}}
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">Vendor AWB Details (Vendor to Admin)</h6>
          </div>
          <div class="card-body">
            <p><strong>AWB Number:</strong> {{ $data['awb_number'] ?? 'N/A' }}</p>
            <p><strong>Courier:</strong> {{ $data['courier_company'] ?? 'N/A' }}</p>
            <p><strong>Tracking:</strong> 
              @if($data['tracking_url'])
                <a href="{{ $data['tracking_url'] }}" target="_blank">Track Package</a>
              @else 
                N/A 
              @endif
            </p>
          </div>
        </div>

        {{-- Packing Slip Fetch Button --}}


      </div>

    </div>

  </div>
  

  {{-- Dispatch Modal --}}
  <x-dispatch-modal :data="$data['customer_shipment']" />

<!-- Packing Slip Modal -->
<div class="modal fade" id="packingSlipModal" tabindex="-1" aria-labelledby="packingSlipLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border p-4" style="font-family: Arial, sans-serif; font-size: 14px;">

      <!-- Modal Header (Don't include in download) -->
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h5 class="modal-title" id="packingSlipLabel">Shipping Label</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Wrapping only download part in this div -->
      <div id="shippingLabelContent" class="p-5">

          <!-- Logos -->
          <div class="row mb-3 text-center align-items-center">
            <div class="col-6">
              <img id="clientLogo" src="" alt="Client Logo" style="max-height: 50px;">
            </div>
            <div class="col-6">
              <img id="delhiveryLogo" src="" alt="Courier Logo" style="max-height: 50px;">
            </div>
          </div>

          <!-- Barcodes -->
          <div class="row mb-4 text-center">
            <div class="col-md-6">
              <label class="fw-bold">Shipment Barcode</label><br>
              <img id="barcodeImgModal" src="" class="img-fluid border p-2 mt-2" style="max-height: 120px;">
            </div>
            <div class="col-md-6">
              <label class="fw-bold">Order ID Barcode</label><br>
              <img id="oidBarcodeImgModal" src="" class="img-fluid border p-2 mt-2" style="max-height: 120px;">
            </div>
          </div>

          <!-- Info -->
          <div class="row g-4">
            <div class="col-md-6">
              <p><strong>Order ID:</strong> <span id="oidText"></span></p>
              <p><strong>Customer:</strong> <span id="customerName"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>From:</strong> <span id="origin"></span></p>
              <p><strong>To:</strong> <span id="destination"></span></p>
            </div>
            <div class="col-md-12">
              <p><strong>Delivery Address:</strong><br><span id="deliveryAddress"></span></p>
            </div>
          </div>
      </div>

      <!-- This won't be included -->
      <!-- Buttons (not included in image download) -->
      <div class="text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
        <!-- Download Shipment Barcode only -->
        <button id="downloadShipmentBarcode" class="btn btn-outline-primary">
          <i class="bi bi-box"></i> Download Shipment Barcode
        </button>

        <!-- Download Entire Shipping Label -->
        <button id="downloadPackingSlip" class="btn btn-outline-success">
          <i class="bi bi-download"></i> Download Shipping Label
        </button>
      </div>


    </div>
  </div>
</div>

  
  



  
</div>
@endsection


