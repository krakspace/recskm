@extends('layouts.app')

@php
    $supervisor = $supervisor ?? false;
    $label = $supervisor ? 'Antrean Supervisor' : 'Dalam Pengajuan';
@endphp

@section('title', $label)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => $supervisor ? 'Pemeriksaan SOP' : 'Pengelolaan SOP'],
        ['label' => $label],
    ]])
@endsection

@section('content')
    @include('partials.sop.assets')

    <div
        id="sop-module-root"
        class="sop-module"
        data-page-mode="{{ $supervisor ? 'supervisor' : 'in_review' }}"
        data-pdf-lib-url="{{ asset('modul-sop-pdf/assets/pdf-lib.min.js') }}"
    >
        {{-- Card Header --}}
        <div class="card sop-card shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="sop-page-header">
                        <h1>{{ $label }}</h1>
                        <p class="text-muted mb-0 mt-1">
                            {{ $supervisor ? 'Simulasi di browser. Periksa pratinjau revisi sebelum memberi keputusan untuk tahap yang aktif.' : 'Pantau tahap pemeriksaan dan buka pratinjau versi yang diajukan.' }}
                        </p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button id="guide" type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                            <i data-icon="help"></i>
                            <span>Panduan</span>
                        </button>
                        <a href="{{ route('pengelolaan.draf-sop') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i data-icon="plus"></i>
                            <span>Buka Draf SOP</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Tabel Daftar SOP Dalam Pengajuan --}}
        <section class="card sop-card shadow-sm" aria-labelledby="reviewTableTitle">
            <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="sop-icon-badge">
                        <i data-icon="clock"></i>
                    </span>
                    <h2 id="reviewTableTitle" class="h6 fw-bold mb-0">{{ $supervisor ? 'Dokumen untuk diperiksa' : 'Daftar SOP Dalam Pengajuan' }}</h2>
                    <span id="reviewCountBadge" class="badge rounded-pill text-bg-light border">0 Dokumen</span>
                </div>

                <div class="w-100" style="max-width: 280px;">
                    <input
                        id="reviewSearchInput"
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Cari judul, nomor, atau unit..."
                        aria-label="Cari SOP dalam pengajuan"
                    >
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 sop-table">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3 py-2">Dokumen SOP</th>
                                <th scope="col" class="py-2">Unit Penyusun</th>
                                <th scope="col" class="py-2">Tahap Pemeriksaan</th>
                                <th scope="col" class="py-2">Waktu Diajukan</th>
                                <th scope="col" class="pe-3 py-2 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reviewListBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include('partials.sop.dialogs', ['includeRevisionRequestModal' => $supervisor])
@endsection
