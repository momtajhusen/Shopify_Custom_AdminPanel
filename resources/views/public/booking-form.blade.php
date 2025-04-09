<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Your Event</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-4">
      <h1>Book Your Event</h1>
      
      @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @if($errors->any())
          <div class="alert alert-danger">
              <ul>
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
      @endif

      <!-- Booking Form -->
      <form action="{{ route('bookings.storePublic') }}" method="POST">
          @csrf
          <div class="form-group">
              <label for="artist_id">Artist ID</label>
              <input type="number" name="artist_id" id="artist_id" class="form-control" value="{{ old('artist_id') }}" required>
          </div>
          <div class="form-group">
              <label for="event_type">Event Type</label>
              <select name="event_type" id="event_type" class="form-control" required>
                  <option value="">Select Occasion</option>
                  <option value="Wedding" {{ old('event_type')=='Wedding' ? 'selected' : '' }}>Wedding</option>
                  <option value="Birthday" {{ old('event_type')=='Birthday' ? 'selected' : '' }}>Birthday</option>
                  <option value="Corporate" {{ old('event_type')=='Corporate' ? 'selected' : '' }}>Corporate</option>
              </select>
          </div>
          <div class="form-group">
              <label for="event_date">Event Date</label>
              <input type="datetime-local" name="event_date" id="event_date" class="form-control" value="{{ old('event_date') }}" required>
          </div>
          <div class="form-group">
              <label for="venue">Venue City</label>
              <input type="text" name="venue" id="venue" class="form-control" value="{{ old('venue') }}" required>
          </div>
          <div class="form-group">
              <label for="budget">Budget</label>
              <input type="text" name="budget" id="budget" class="form-control" value="{{ old('budget') }}" required>
          </div>
          <div class="form-group">
              <label for="full_name">Full Name</label>
              <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name') }}" required>
          </div>
          <div class="form-group">
              <label for="email">Email Id</label>
              <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
          </div>
          <div class="form-group">
              <label for="phone">Mobile Number</label>
              <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" required>
          </div>
          <div class="form-group">
              <label for="details">Additional Info</label>
              <textarea name="details" id="details" class="form-control">{{ old('details') }}</textarea>
          </div>
          <br>
          <button type="submit" class="btn btn-primary">Get Quote</button>
      </form>
  </div>

  <!-- OTP Modal -->
  <div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-labelledby="otpModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form action="{{ route('bookings.otpVerify', session('current_booking_id') ?? 0) }}" method="POST">
        @csrf
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="otpModalLabel">Enter OTP</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Please enter the 4-digit OTP sent to your email/phone.</p>
            <input type="text" name="otp" class="form-control" maxlength="4" required>
            @if($errors->has('otp'))
                <span class="text-danger">{{ $errors->first('otp') }}</span>
            @endif
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Verify OTP</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Booking Confirmed!</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='{{ route('bookings.createPublicBooking') }}'">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>Your booking has been confirmed successfully. We will contact you soon.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('bookings.createPublicBooking') }}'">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery and Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
      $(document).ready(function(){
          @if(session('showOtpModal'))
              $('#otpModal').modal('show');
          @endif

          @if(session('bookingSuccess'))
              $('#successModal').modal('show');
          @endif
      });
  </script>
</body>
</html>
