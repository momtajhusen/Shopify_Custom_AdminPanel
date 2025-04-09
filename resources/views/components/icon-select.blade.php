 
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
        <select 
            id="{{ $name }}" 
            name="{{ $name }}" 
            class="form-select select2"
            data-allow-clear="true"
        >
            @foreach($options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
 


 @push('styles')
  
 <!-- endbuild -->

 <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
 <link rel="stylesheet" href="{{ asset('assets/vendor/libs/tagify/tagify.css') }}" />
 <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}" />
 <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
@endpush

@push('scripts')
    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/tagify/tagify.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
 

    <!-- Page JS -->
    <script src="{{ asset('assets/js/forms-selects.js') }}"></script>
    <script src="{{ asset('assets/js/forms-tagify.js') }}"></script>
    <script src="{{ asset('assets/js/forms-typeahead.js') }}"></script>
@endpush