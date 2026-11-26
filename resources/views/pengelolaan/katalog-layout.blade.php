@extends('layouts.app')

@section('title', $label)

@push('styles')
    <link rel="stylesheet" href="{{ asset('modul-sop-pdf/blade-sop.css') }}">
@endpush

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => $section],
        ['label' => $label],
    ]])
@endsection

@section('content')
    <div class="sop-module">
        <header class="card sop-card shadow-sm mb-4">
            <div class="card-body sop-page-header p-3 p-md-4">
                <h1>{{ $label }}</h1>
                <p class="text-muted mb-0 mt-1">@yield('description')</p>
            </div>
        </header>
        @yield('workspace')
    </div>
@endsection
