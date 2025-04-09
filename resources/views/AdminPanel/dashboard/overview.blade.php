@extends('AdminPanel.admin-layout')

@section('styles')
  {{-- extra styles if needed --}}
@endsection

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">
 
      {{-- Total Vendors --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-primary h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="icon-base ti tabler-building-store icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $vendorCount ?? 0 }}</h4>
            </div>
            <p class="mb-1">Total Vendors</p>
            <p class="mb-0"><small class="text-body-secondary">Last week</small></p>
          </div>
        </div>
      </div>


      {{-- Total Order Products --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-info h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-info">
                  <i class="icon-base ti tabler-shopping-cart icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $totalProducts ?? 0 }}</h4>
            </div>
            <p class="mb-1">Total Order Products</p>
            <p class="mb-0"><small class="text-body-secondary">Last week</small></p>
          </div>
        </div>
      </div>

      {{-- Assigned Order Products --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-success h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="icon-base ti tabler-truck icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $assignedProducts ?? 0 }}</h4>
            </div>
            <p class="mb-1">Assigned Order Products</p>
            <p class="mb-0"><small class="text-body-secondary">Last week</small></p>
          </div>
        </div>
      </div>

      {{-- In‑Process Products --}}
      {{-- <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-warning h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="icon-base ti tabler-clock-hour-9 icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $inProcessProducts ?? 0 }}</h4>
            </div>
            <p class="mb-1">In‑Process Products</p>
            <p class="mb-0"><small class="text-body-secondary">Last week</small></p>
          </div>
        </div>
      </div> --}}

      {{-- Unassigned Products --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-danger h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="icon-base ti tabler-alert-triangle icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $unassignedProducts ?? 0 }}</h4>
            </div>
            <p class="mb-1">Unassigned Order Products</p>
            <p class="mb-0"><small class="text-body-secondary">Last week</small></p>
          </div>
        </div>
      </div>

      {{-- Completed Order Products --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-success h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="icon-base ti tabler-check icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $completedAssignments ?? 0 }}</h4>
            </div>
            <p class="mb-1">Completed Order Products</p>
            <p class="mb-0"><small class="text-body-secondary">Last week</small></p>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
