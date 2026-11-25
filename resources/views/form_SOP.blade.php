@extends('layouts.app')

@section('title', 'Buat SOP')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Pengelolaan SOP'],
        ['label' => 'Buat SOP'],
    ]])
@endsection

@section('content')
    <h1>Buat SOP</h1>
@endsection
