@extends('layouts.app') <!-- आपकी मुख्य लेआउट फ़ाइल -->

@section('content')
<div class="container">
    <h1 class="mt-4">Book Your Event</h1>
    
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Booking Form -->
    <form id="bookingForm" action="{{ route('bookings.storePublic') }}" method="POST">
        @csrf

        <!-- Artist (required) -->
        <div class="form-group">
            <label for="artist_id">Artist</label>
            <input type="number" name="artist_id" id="artist_id" class="form-control" value="{{ old('artist_id') }}" required>
        </div>
        
        <!-- Event Type -->
        <div class="form-group">
            <label for="event_type">What's the Occasion</label>
            <select name="event_type" id="event_type" class="form-control" required>
                <option value="">-- Select Occasion --</option>
                <option value="Wedding" {{ old('event_type')=='Wedding' ? 'selected' : '' }}>Wedding</option>
                <option value="Birthday" {{ old('event_type')=='Birthday' ? 'selected' : '' }}>Birthday</option>
                <option value="Corporate" {{ old('event_type')=='Corporate' ? 'selected' : '' }}>Corporate</option>
            </select>
        </div>

        <!-- Event Date -->
        <div class="form-group">
            <label for="event_date">Event Date</label>
            <input type="datetime-local" name="event_date" id="event_date" class="form-control" value="{{ old('event_date') }}" required>
        </div>

        <!-- Venue City -->
        <div class="form-group">
            <label for="venue">Venue City</label>
            <input type="text" name="venue" id="venue" class="form-control" placeholder="Enter your Venue" value="{{ old('venue') }}" required>
        </div>

        <!-- Budget -->
        <div class="form-group">
            <label for="budget">Budget</label>
            <input type="text" name="budget" id="budget" class="form-control" placeholder="Specify your budget" value="{{ old('budget') }}" required>
        </div>

        <!-- Full Name -->
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter your name" value="{{ old('full_name') }}" required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Email Id</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" value="{{ old('email') }}" required>
        </div>

        <!-- Mobile Number -->
        <div class="form-group">
            <label for="phone">Mobile Number</label>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number" value="{{ old('phone') }}" required>
        </div>

        <!-- Additional Info -->
        <div class="form-group">
            <label for="details">Additional Info</label>
            <textarea name="details" id="details" class="form-control" placeholder="Any extra details">{{ old('details') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Get Quote</button>
    </form>
</div>

<!-- OTP Modal -->
<div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="otpForm" action="{{ route('bookings.otpVerify', session('bookingId')) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">OTP Verification</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>Please enter the 4-digit OTP sent to your mobile/email.</p>
                    <div class="form-group">
                        <label for="otp">OTP</label>
                        <input type="text" name="otp" id="otp" class="form-control" required maxlength="4">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Verify OTP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Confirmed!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Your booking has been successfully confirmed. We will contact you soon.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<!-- jQuery and Bootstrap JS (ensure these are loaded in your project) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // यदि session में otpRequired है, तो OTP Modal दिखाएं
    @if(session('otpRequired'))
        $(document).ready(function(){
            $('#otpModal').modal('show');
        });
    @endif

    // यदि session में bookingSuccess है, तो Success Modal दिखाएं
    @if(session('bookingSuccess'))
        $(document).ready(function(){
            $('#successModal').modal('show');
        });
    @endif
</script>
@endsection
