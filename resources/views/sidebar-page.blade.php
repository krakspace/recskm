@extends('layouts.app')

@section('title', $label)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => $section],
        ['label' => $label],
    ]])
@endsection

@section('content')
    <h1>{{ $label }}</h1>
@endsection
