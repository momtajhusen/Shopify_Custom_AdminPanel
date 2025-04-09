@extends('AdminPanel.admin-layout')

@section('styles')
  <style>
    /* ensure table can scroll inside card on small screens */
    .card .table-responsive {
      overflow-x: auto;
    }
  </style>
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between mb-6">
      <div>
        <span class="h5">Product #{{ $data['order_id'] }}</span>
        <span class="badge bg-label-info ms-1">
          {{ ucfirst(str_replace('_',' ',$data['status'])) }}
        </span>
        <p class="mb-0">{{ $data['assigned_at'] }}</p>
      </div>
    </div>

    <div class="row">
      {{-- LEFT – product + calculations --}}
      <div class="col-lg-8">
        <div class="card mb-4">

          {{-- table responsive wrapper --}}
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th></th><th></th>
                  <th class="w-50">Product</th>
                  <th class="w-25">Order&nbsp;Price</th>
                  <th class="w-25">Qty</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td></td>
                  <td><img src="{{ $data['product_img'] }}" width="50"></td>
                  <td>{{ $data['product_title'] }}</td>
                  <td>₹{{ number_format($data['order_price'],2) }}</td>
                  <td>{{ $data['quantity'] }}</td>
                  <td>₹{{ number_format($data['order_price']*$data['quantity'],2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          {{-- summary --}}
          <div class="d-flex justify-content-end m-4">
            <div>
              <div class="d-flex mb-1">
                <span class="w-100">Subtotal:</span>
                <strong>₹{{ number_format($data['order_price']*$data['quantity'],2) }}</strong>
              </div>
              <div class="d-flex mb-1">
                <span class="w-100">Vendor Price:</span>
                <strong>
                  {{ $data['vendor_price']!==null ? '₹'.number_format($data['vendor_price'],2) : 'N/A' }}
                </strong>
              </div>
              <div class="d-flex">
                <span class="w-100">Margin:</span>
                <strong>
                  {{ $data['margin']!==null ? '₹'.number_format($data['margin'],2) : 'N/A' }}
                  @if(!is_null($data['margin']))
                    <span class="text-{{ $data['margin']<0?'danger':'success' }} ms-1">
                      {{ $data['margin']<0 ? 'Loss' : 'Profit' }}
                    </span>
                  @endif
                </strong>
              </div>
            </div>
          </div>
        </div>

        {{-- Timeline --}}
        <div class="card">
          <div class="card-header"><h5 class="m-0">Status activity</h5></div>
          <div class="card-body pt-1">
            <ul class="timeline mb-0">
              @foreach($timeline as $entry)
              <li class="timeline-item timeline-item-transparent border-primary">
                <span class="timeline-point timeline-point-primary"></span>
                <div class="timeline-event">
                  <div class="timeline-header">
                    <h6 class="mb-0">{{ $entry['label'] }}</h6>
                    @if($entry['time']) <small>{{ $entry['time'] }}</small> @endif
                  </div>
                  <p class="mt-2 mb-0">{{ $entry['desc'] }}</p>
                </div>
              </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      {{-- RIGHT – vendor + AWB --}}
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header"><h5 class="m-0">Vendor details</h5></div>
          <div class="card-body">
            <h6 class="mb-1">{{ $data['vendor_name'] }}</h6>
            <p class="mb-1">Vendor ID: #{{ $data['vendor_id'] }}</p>
            <p class="mb-1">Email&nbsp;: {{ $data['vendor_email'] }}</p>
            <p class="mb-0">Phone&nbsp;: {{ $data['vendor_phone'] }}</p>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h5 class="m-0">AWB Details</h5></div>
          <div class="card-body">
            <p><strong>AWB Number:</strong> {{ $data['awb_number'] ?? 'N/A' }}</p>
            <p><strong>Courier:</strong> {{ $data['courier_company'] ?? 'N/A' }}</p>
            <p><strong>Tracking:</strong>
              @if($data['tracking_url'])
                <a href="{{ $data['tracking_url'] }}" target="_blank">Track package</a>
              @else N/A @endif
            </p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection