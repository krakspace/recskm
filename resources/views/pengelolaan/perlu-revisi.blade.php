@extends('layouts.app')

@section('title', 'Perlu Revisi')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Pengelolaan SOP'],
        ['label' => 'Perlu Revisi'],
    ]])
@endsection

@section('content')
    @include('partials.sop.assets')

    <div
        id="sop-module-root"
        class="sop-module"
        data-page-mode="needs_revision"
        data-draf-sop-url="{{ route('pengelolaan.draf-sop') }}"
        data-pdf-lib-url="{{ asset('modul-sop-pdf/assets/pdf-lib.min.js') }}"
    >
        {{-- Card Header --}}
        <div class="card sop-card shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="sop-page-header">
                        <h1>Perlu Revisi</h1>
                        <p class="text-muted mb-0 mt-1">
                            Daftar SOP yang dikembalikan pemeriksa. Klik tombol <strong>Revisi</strong> untuk membuat draf SOP baru dengan nomor revisi berikutnya di halaman Draf SOP tanpa menghapus versi awalnya.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button id="guide" type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                            <i data-icon="help"></i>
                            <span>Panduan</span>
                        </button>
                        <a href="{{ route('pengelolaan.draf-sop') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i data-icon="folder"></i>
                            <span>Buka Draf SOP</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Tabel Daftar SOP Perlu Revisi --}}
        <section class="card sop-card shadow-sm" aria-labelledby="revisionTableTitle">
            <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="sop-icon-badge warning-badge">
                        <i data-icon="alert"></i>
                    </span>
                    <h2 id="revisionTableTitle" class="h6 fw-bold mb-0">Daftar SOP Perlu Revisi</h2>
                    <span id="revisionCountBadge" class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        0 Dokumen
                    </span>
                </div>

                <div class="w-100" style="max-width: 280px;">
                    <input
                        id="revisionSearchInput"
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Cari judul, nomor, atau catatan..."
                        aria-label="Cari SOP perlu revisi"
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
                                <th scope="col" class="py-2">Catatan Pemeriksa</th>
                                <th scope="col" class="py-2">Waktu Dikembalikan</th>
                                <th scope="col" class="pe-3 py-2 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="revisionListBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include('partials.sop.dialogs')
@endsection
