@extends('layouts.app')

@section('title', 'Draf SOP')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Pengelolaan SOP'],
        ['label' => 'Draf SOP'],
    ]])
@endsection

@section('content')
    @include('partials.sop.assets')

    <div
        id="sop-module-root"
        class="sop-module"
        data-page-mode="draft"
        @if (Route::has('draft-backups.index'))
            data-backup-url="{{ route('draft-backups.index') }}"
            data-backup-session="{{ hash('sha256', session()->getId()) }}"
            data-token="{{ csrf_token() }}"
        @endif
        data-draf-sop-url="{{ route('pengelolaan.draf-sop') }}"
        data-pdf-lib-url="{{ asset('modul-sop-pdf/assets/pdf-lib.min.js') }}"
    >
        {{-- Card Header --}}
        <div class="card sop-card shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="sop-page-header">
                        <h1>Draf SOP</h1>
                        <p class="text-muted mb-0 mt-1">
                            Susun SOP baru (otomatis mulai dari Revisi 1) atau lanjutkan revisi berikutnya melalui editor dan pratinjau PDF di atas.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button id="guide" type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                            <i data-icon="help"></i>
                            <span>Panduan</span>
                        </button>
                        <button id="newDraftBtn" type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                            <i data-icon="plus"></i>
                            <span>Draf Baru</span>
                        </button>
                        <button id="saveDraftBtn" type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                            <i data-icon="save"></i>
                            <span>Simpan Draf</span>
                        </button>
                        <button id="export" type="button" class="btn btn-outline-dark d-inline-flex align-items-center gap-2">
                            <i data-icon="download"></i>
                            <span>Unduh PDF</span>
                        </button>
                        <button id="submitSopBtn" type="button" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i data-icon="send"></i>
                            <span>Ajukan SOP</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if (Route::has('draft-backups.index'))
            <div class="mb-4">
                <div class="form-check">
                    <input id="databaseBackup" type="checkbox" class="form-check-input">
                    <label for="databaseBackup" class="form-check-label">Gunakan cadangan database untuk sesi ini</label>
                </div>
                <div id="backupOptions" hidden>
                    <label for="serverDrafts" class="form-label">Draf tersimpan di database untuk sesi ini</label>
                    <div class="input-group">
                        <select id="serverDrafts" class="form-select" aria-describedby="backupStatus"></select>
                        <button id="restoreDraft" type="button" class="btn btn-outline-primary" disabled>Buka draf</button>
                    </div>
                    <small id="backupStatus" role="status">Autosave menyimpan perubahan di browser. Gunakan Simpan Draf untuk menyimpan ke database.</small>
                </div>
            </div>
        @endif

        {{-- Card Konteks Revisi (tampil otomatis saat mengedit draf hasil revisi ke-2 dst.) --}}
        <section id="draftRevisionBanner" class="card sop-card sop-revision-card shadow-sm mb-4" aria-labelledby="draftRevisionHeading" hidden>
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="sop-icon-badge warning-badge">
                        <i data-icon="history"></i>
                    </span>
                    <h2 id="draftRevisionHeading" class="h6 fw-bold mb-0 text-dark">Catatan Revisi</h2>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="draftRevisionMeta" class="small text-muted fw-medium"></span>
                    <button id="viewParentPdfBtn" type="button" class="btn btn-sm btn-outline-dark" hidden>
                        Lihat PDF Revisi Sebelumnya
                    </button>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <p id="draftRevisionNoteText" class="mb-0 text-dark small"></p>
            </div>
        </section>

        {{-- Bagian Atas: Card Editor Formulir + Card Pratinjau PDF --}}
        @include('partials.sop.editor-preview-cards', [
            'editorTitle' => 'Editor Formulir SOP',
        ])

        {{-- Bagian Bawah: Card Daftar Draf SOP & Riwayat Revisi (Audit) --}}
        <section class="card sop-card shadow-sm mt-4" aria-labelledby="draftTableTitle">
            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="sop-icon-badge draft-badge">
                            <i data-icon="folder"></i>
                        </span>
                        <h2 id="draftTableTitle" class="h6 fw-bold mb-0">Daftar Draf & Riwayat Revisi</h2>
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Filter daftar draf">
                        <button type="button" class="btn btn-outline-primary active" data-draft-filter="active">
                            Draf Aktif
                            <span id="draftCountBadge" class="badge text-bg-primary ms-1">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-draft-filter="history">
                            Riwayat Revisi (Audit)
                            <span id="historyCountBadge" class="badge text-bg-secondary ms-1">0</span>
                        </button>
                    </div>
                </div>

                <div class="w-100" style="max-width: 280px;">
                    <input
                        id="draftSearchInput"
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Cari judul, nomor, atau unit..."
                        aria-label="Cari draf SOP"
                    >
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 sop-table">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3 py-2">Dokumen SOP</th>
                                <th scope="col" class="py-2">Nomor Revisi</th>
                                <th scope="col" class="py-2">Unit Penyusun</th>
                                <th scope="col" class="py-2">Terakhir Diperbarui</th>
                                <th scope="col" class="pe-3 py-2 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="draftListBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @include('partials.sop.dialogs')
@endsection
