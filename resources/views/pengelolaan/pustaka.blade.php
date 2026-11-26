@extends('pengelolaan.katalog-layout')

@section('description', 'Telusuri versi SOP dan tanggal berlakunya menggunakan koleksi contoh unit Administrasi.')

@section('workspace')
    @php
        $query = trim(request()->string('q')->toString());
        $documents = collect(config('sop_demo.documents'))->filter(function ($doc) use ($query) {
            $visible = match (request()->route()->getName()) {
                'pustaka.sop-berlaku' => $doc['status'] === 'Berlaku',
                'pustaka.arsip-sop' => $doc['status'] !== 'Berlaku',
                default => true,
            };

            return $visible && str_contains(strtolower(implode(' ', [$doc['number'], $doc['title'], $doc['unit']])), strtolower($query));
        });
    @endphp
    <section class="card sop-card shadow-sm">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <h2 class="h6 fw-bold mb-0">Daftar dokumen <span class="badge rounded-pill text-bg-light border">{{ $documents->count() }}</span></h2>
            <form class="d-flex gap-2" method="get">
                <input class="form-control form-control-sm" type="search" name="q" value="{{ $query }}" placeholder="Judul, nomor, atau unit" aria-label="Cari dokumen">
                <button class="btn btn-sm btn-outline-primary">Cari</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 sop-table">
                <thead><tr><th class="ps-3">Dokumen</th><th>Unit</th><th>Tanggal efektif</th><th>Status</th><th>Detail</th></tr></thead>
                <tbody>
                    @forelse ($documents as $uuid => $doc)
                        <tr>
                            <td class="ps-3 py-3"><strong>{{ $doc['title'] }}</strong><div class="small text-muted">{{ $doc['number'] }} · Revisi {{ $doc['revision'] }}</div></td>
                            <td>{{ $doc['unit'] }}</td><td>{{ $doc['effective'] }}</td>
                            <td><span class="badge rounded-pill {{ $doc['status'] === 'Berlaku' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $doc['status'] }}</span></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('verifikasi.publik', $uuid) }}">Detail versi</a>
                                <a class="btn btn-sm btn-outline-secondary" download href="{{ asset('modul-sop-pdf/contoh/'.$uuid.'.pdf') }}">Unduh PDF</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Dokumen tidak ditemukan. <a href="{{ url()->current() }}">Hapus pencarian</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
