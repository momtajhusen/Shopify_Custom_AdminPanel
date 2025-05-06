@props(['data'])

<div class="modal fade" id="dispatchOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Dispatch Order to Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="dispatchOrderForm" class="needs-validation" novalidate method="POST" action="{{ route('order.createShipment') }}">
        @csrf

        <div class="modal-body">
          <div class="accordion" id="dispatchAccordion">

            {{-- Pickup Location --}}
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pickupCollapse">
                  Pickup Location Details
                </button>
              </h2>
              <div id="pickupCollapse" class="accordion-collapse collapse" data-bs-parent="#dispatchAccordion">
                <div class="accordion-body">
                  <div class="row gx-2">
                    @foreach(['name','add','city','state','pin','phone','country'] as $f)
                      <div class="col-md-4 mb-3">
                        <label>{{ ucfirst($f) }}</label>
                        <input type="text" name="pickup_location[{{ $f }}]" class="form-control" value="{{ $data['pickup_location'][$f] }}" readonly>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

            {{-- Shipment Details --}}
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#shipmentCollapse">
                  Shipment Details
                </button>
              </h2>
              <div id="shipmentCollapse" class="accordion-collapse collapse show" data-bs-parent="#dispatchAccordion">
                <div class="accordion-body">
                  <div class="row gx-2">
                    @php
                      $ship = $data['shipments'][0];
                      $fields = [
                        'name', 'phone', 'add', 'payment_mode', 'total_amount', 'quantity', 
                        'city', 'state', 'pin', 'country', 'order', 'cod_amount',
                        'return_name', 'return_add', 'return_city', 'return_state', 
                        'return_pin', 'return_country', 'return_phone'
                      ];
                    @endphp

                    @foreach($fields as $f)
                      <div class="col-md-4 mb-3">
                        <label>{{ ucwords(str_replace('_',' ',$f)) }}</label>
                        <input type="text" name="shipments[0][{{ $f }}]" class="form-control" value="{{ $ship[$f] ?? '' }}" required>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        {{-- Hidden Fields for saving product_id and order_id (NOT sent to API) --}}
        <input type="hidden" name="product_id" value="{{ $data['shipments'][0]['cus_product_id'] }}">
        <input type="hidden" name="order_id" value="{{ $data['shipments'][0]['cus_order_id'] }}">

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Create Shipment</button>
        </div>

      </form>

    </div>
  </div>
</div>

{{-- SweetAlert + AJAX --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('dispatchOrderForm');
  const btn = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', e => {
    e.preventDefault();
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
        'Accept': 'application/json'
      },
      body: new FormData(form)
    })
    .then(r => r.json())
    .then(data => {
      console.log(data);
      btn.disabled = false;
      btn.innerHTML = orig;
      bootstrap.Modal.getInstance(document.getElementById('dispatchOrderModal')).hide();

      // 🔥 Build the Error Message properly
      let errorText = data.message;
      if (data.remarks) {
        errorText += '\n\nReason: ' + data.remarks;
      }

      Swal.fire({
        icon: data.success ? 'success' : 'error',
        title: data.success ? 'Success' : 'Error',
        text: errorText,
        confirmButtonText: 'OK',
        timer: 0
      }).then(() => {
        if (data.success) window.location.reload();
      });
    })
    .catch(error => {
      console.error('Dispatch Error:', error);
      btn.disabled = false;
      btn.innerHTML = orig;
      bootstrap.Modal.getInstance(document.getElementById('dispatchOrderModal')).hide();
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Something went wrong. Please try again.',
        confirmButtonText: 'OK',
        timer: 0
      });
    });
  });
});

</script>
