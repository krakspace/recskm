@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda'],
    ]])
@endsection

@section('content')
    <section class="dashboard-intro" aria-labelledby="dashboard-heading">
        <h1 id="dashboard-heading">Beranda</h1>
        <p class="mb-0 text-body-secondary">Selamat datang di halaman dashboard.</p>
    </section>
@endsection
