@extends('AdminPanel.admin-layout')

@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}" />
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
@endsection

@section('scripts')
  <script src="{{ asset('assets/js/form-validation.js') }}"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    $(document).ready(function(){
      @if(session('success'))
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: '{{ session("success") }}',
          background: '#1e1e2f',
          color: '#fff'
        });
      @endif

      @if($errors->any())
        let errorMessages = `@foreach($errors->all() as $error){{ $error }}\n @endforeach`;
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: errorMessages,
          background: '#1e1e2f',
          color: '#fff'
        });
      @endif
    });
  </script>
@endsection

@section('content')
@php
  $admin = Auth::check() ? Auth::user() : null;
  $vendor = Auth::guard('vendor')->check() ? Auth::guard('vendor')->user() : null;
@endphp

<div class="container mt-4">
  <div class="row g-6">
  
    <!-- Password Change Card (for both admin & vendor) -->
    <div class="col-lg-6 col-sm-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Change Password</h5>
          <form class="needs-validation" novalidate action="{{ route('admin.password.change') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
              <label for="old_password" class="form-label">Current Password</label>
              <input type="password" name="old_password" class="form-control" id="old_password" required>
              <div class="invalid-feedback">Please enter your current password.</div>
            </div>

            <div class="form-password-toggle mb-3">
              <label class="form-label" for="new_password">New Password</label>
              <div class="input-group">
                <input type="password" name="new_password" class="form-control" id="new_password" required>
                <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
              </div>
              <div class="invalid-feedback">Please enter your new password.</div>
            </div>

            <div class="form-password-toggle mb-3">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <div class="input-group">
                <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
              </div>
              <div class="invalid-feedback">Passwords do not match.</div>
            </div>

            <button type="submit" class="btn btn-primary">Change Password</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Admin/Vendor Profile Update Card --}}
    @if($admin || $vendor)
    <div class="col-lg-6 col-sm-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Update Profile</h5>
          <form action="{{ route('update.profile') }}" method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            {{-- Common Name --}}
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" name="name" class="form-control" id="name" value="{{ $admin?->name ?? $vendor?->name }}" required>
              <div class="invalid-feedback">Please enter your name.</div>
            </div>

            {{-- Email --}}
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" name="email" class="form-control" id="email" value="{{ $admin?->email ?? $vendor?->email }}" required>
              <div class="invalid-feedback">Please enter a valid email.</div>
            </div>

            {{-- Phone --}}
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" id="phone" value="{{ $admin?->phone ?? $vendor?->phone }}">
            </div>

            {{-- Address (Vendor only) --}}
            @if($vendor)
            <div class="mb-3">
              <label for="address" class="form-label">Address</label>
              <textarea name="address" class="form-control" id="address" rows="2">{{ $vendor->address }}</textarea>
            </div>
            @endif
            
            <button type="submit" class="btn btn-primary">Update Profile</button>

          </form>
        </div>
      </div>
    </div>
    @endif

        {{-- Only show for Admin --}}
        @if($admin)
        <!-- Configuration Settings Card -->
        <div class="col-lg-6 col-sm-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Configuration</h5>
              <form class="needs-validation" novalidate action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
  
                <div class="mb-3">
                  <label for="whatsapp_api_token" class="form-label">WhatsApp API Token</label>
                  <input type="text" name="whatsapp_api_token" class="form-control" id="whatsapp_api_token" value="{{ old('whatsapp_api_token', $settings->whatsapp_api_token ?? '') }}" required>
                  <div class="invalid-feedback">Please enter WhatsApp API Token.</div>
                </div>
  
                <button type="submit" class="btn btn-primary">Update Configuration</button>
              </form>
            </div>
          </div>
        </div>
      @endif
  

  </div>
</div>

@endsection
 