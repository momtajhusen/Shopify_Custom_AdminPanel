@extends('AdminPanel.admin-layout')

@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
@endsection

@section('scripts')
  <script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endsection

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- Button to trigger Add Vendor Modal -->
    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addVendorModal">
        Add New Vendor
    </button>

    <!-- Add Vendor Modal -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="needs-validation" novalidate action="{{ route('vendors.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Vendor Name</label>
                            <input type="text" name="name" class="form-control" id="name" required>
                            <div class="invalid-feedback">Please enter vendor name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="vendor_code" class="form-label">Vendor Code</label>
                            <input type="text" name="vendor_code" class="form-control" id="vendor_code" required>
                            <div class="invalid-feedback">Please enter vendor code.</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" id="email" required>
                            <div class="invalid-feedback">Please enter valid email.</div>
                        </div>
                        <div class="form-password-toggle mb-3">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    id="password"
                                    required
                                    placeholder="********" />
                                <span class="input-group-text cursor-pointer">
                                    <i class="icon-base ti tabler-eye-off"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="number" name="phone"  max="10" class="form-control phone" id="phone">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" id="address">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vendor List Table -->
    <div class="card mt-4">
        <h5 class="card-header">Vendor List</h5>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Vendor Code</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td>{{ $vendor->id }}</td>
                        <td>{{ $vendor->name }}</td>
                        <td>{{ $vendor->vendor_code }}</td>
                        <td>{{ $vendor->email }}</td>
                        <td>{{ $vendor->phone }}</td>
                        <td>{{ $vendor->address }}</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="ti tabler-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <!-- Trigger Edit Modal -->
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editVendor{{ $vendor->id }}">
                                        <i class="ti tabler-pencil me-1"></i> Edit
                                    </button>
                                    <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="ti tabler-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editVendor{{ $vendor->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                            <h5 class="modal-title">Edit Vendor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form class="needs-validation" novalidate action="{{ route('vendors.update', $vendor->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="name_{{ $vendor->id }}" class="form-label">Vendor Name</label>
                                        <input type="text" name="name" class="form-control" id="name_{{ $vendor->id }}" value="{{ $vendor->name }}" required>
                                        <div class="invalid-feedback">Please enter vendor name.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="vendor_code_{{ $vendor->id }}" class="form-label">Vendor Code</label>
                                        <input type="text" name="vendor_code" class="form-control" id="vendor_code_{{ $vendor->id }}" value="{{ $vendor->vendor_code }}" required>
                                        <div class="invalid-feedback">Please enter vendor code.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email_{{ $vendor->id }}" class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" id="email_{{ $vendor->id }}" value="{{ $vendor->email }}" required>
                                        <div class="invalid-feedback">Please enter valid email.</div>
                                    </div>
                                    <div class="form-password-toggle mb-3">
                                        <label class="form-label" for="password_{{ $vendor->id }}">Password (Leave blank to keep unchanged)</label>
                                        <div class="input-group">
                                            <input
                                                type="password"
                                                name="password"
                                                class="form-control"
                                                id="password_{{ $vendor->id }}"
                                                placeholder="********" />
                                            <span class="input-group-text cursor-pointer">
                                                <i class="icon-base ti tabler-eye-off"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone_{{ $vendor->id }}" class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control phone" id="phone_{{ $vendor->id }}" value="{{ $vendor->phone }}"/>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address_{{ $vendor->id }}" class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control" id="address_{{ $vendor->id }}" value="{{ $vendor->address }}"/>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-success">Update Vendor</button>
                                </div>
                            </form>
                        </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Flash Auto Close -->
<script>
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function(alert) {
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);



    $(document).ready(function(){
  $('.phone').on('input', function(){
    const $this = $(this);
    let val = $this.val();

    if (val.length > 10) {
      $this.val(val.slice(0, 10));
    }
  });
});

</script>
@endsection
