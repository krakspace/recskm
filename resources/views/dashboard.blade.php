@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda'],
    ]])
@endsection

@section('content')
    <h1>Beranda</h1>
    <p>Selamat datang di halaman dashboard.</p>
@endsection
