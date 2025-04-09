@extends('AdminPanel.admin-layout')

@section('content')
<div class="content-wrapper">
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">

      {{-- Total Orders --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-dark h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-dark">
                  <i class="icon-base ti tabler-list-check icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $totalAssigned ?? 0 }}</h4>
            </div>
            <p class="mb-1">Total Orders</p>
            <p class="mb-0"><small class="text-body-secondary">All Assigned Orders</small></p>
          </div>
        </div>
      </div>

      {{-- Assigned Orders --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-success h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-success">
                  <i class="icon-base ti tabler-truck-delivery icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $assignedCount ?? 0 }}</h4>
            </div>
            <p class="mb-1">Assigned Orders</p>
            <p class="mb-0"><small class="text-body-secondary">Current Assignments</small></p>
          </div>
        </div>
      </div>

      {{-- Pending Orders --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-warning h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="icon-base ti tabler-clock-hour-9 icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $pending ?? 0 }}</h4>
            </div>
            <p class="mb-1">Pending Orders</p>
            <p class="mb-0"><small class="text-body-secondary">Waiting for Delivery</small></p>
          </div>
        </div>
      </div>

      {{-- Delivered Orders --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-primary h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="icon-base ti tabler-check icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $deliveredCount ?? 0 }}</h4>
            </div>
            <p class="mb-1">Delivered Orders</p>
            <p class="mb-0"><small class="text-body-secondary">Completed Deliveries</small></p>
          </div>
        </div>
      </div>

      {{-- Cancelled Orders --}}
      <div class="col-lg-4 col-sm-6">
        <div class="card card-border-shadow-danger h-100">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="icon-base ti tabler-ban icon-28px"></i>
                </span>
              </div>
              <h4 class="mb-0">{{ $cancelledCount ?? 0 }}</h4>
            </div>
            <p class="mb-1">Cancelled Orders</p>
            <p class="mb-0"><small class="text-body-secondary">Not Delivered</small></p>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
