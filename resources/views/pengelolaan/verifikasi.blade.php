@extends('pengelolaan.katalog-layout')

@section('description', 'Verifikasi dokumen contoh berdasarkan identitas revisi dan sidik berkas PDF.')

@section('workspace')
    @php
        $documents = config('sop_demo.documents');
        $uuid = $uuid ?? trim(request()->string('uuid')->toString());
        $document = $document ?? ($documents[$uuid] ?? null);
    @endphp
    @unless (request()->routeIs('verifikasi.publik'))
        <section class="card sop-card shadow-sm mb-4">
            <form class="card-body p-3 p-md-4" method="get">
                <label class="form-label fw-semibold" for="verifyUuid">UUID dari QR dokumen</label>
                <div class="input-group">
                    <input class="form-control" name="uuid" id="verifyUuid" value="{{ $uuid }}" required list="exampleIds" placeholder="Pilih revisi contoh atau masukkan UUID">
                    <datalist id="exampleIds">
                        @foreach ($documents as $id => $example)
                            <option value="{{ $id }}">{{ $example['number'] }} · Revisi {{ $example['revision'] }}</option>
                        @endforeach
                    </datalist>
                    <button class="btn btn-primary">Pilih dokumen</button>
                </div>
                @if ($uuid && !$document)
                    <p class="text-danger mt-2 mb-0" role="status">UUID tidak ditemukan di koleksi contoh.</p>
                @endif
            </form>
        </section>
    @endunless
    @if ($document)
        <section class="card sop-card shadow-sm mb-4">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h2 class="h6 fw-bold mb-0">{{ $document['title'] }}</h2>
                <span class="badge text-bg-light border">{{ $document['status'] }}</span>
            </div>
            <div class="card-body">
                <p>{{ $document['number'] }} · Revisi {{ $document['revision'] }} · Efektif {{ $document['effective'] }}</p>
                <h3 class="h6">Riwayat pengesahan contoh</h3>
                <div class="table-responsive"><table class="table table-sm small">
                    <thead><tr><th>Tindakan</th><th>Jabatan</th><th>Waktu</th><th>UUID tindakan</th></tr></thead>
                    <tbody>@foreach ($document['decisions'] as $decision)
                        <tr><td>{{ $decision['action'] }}</td><td>{{ $decision['role'] }}</td><td>{{ $decision['at'] }}</td><td>{{ $decision['uuid'] }}</td></tr>
                    @endforeach</tbody>
                </table></div>
                <a href="{{ route('pustaka.seluruh-sop-terbit', ['q' => $document['number']]) }}">Lihat riwayat versi di pustaka contoh</a>
            </div>
        </section>
        <section class="card sop-card shadow-sm">
            <div class="card-header"><h2 class="h6 fw-bold mb-0">Periksa integritas PDF</h2></div>
            <div class="card-body">
                <label for="verifyPdf" class="form-label">Pilih salinan PDF (maksimum 20 MB)</label>
                <input id="verifyPdf" type="file" class="form-control" accept=".pdf,application/pdf" data-hash="{{ $document['sha256'] }}">
                <p id="hashResult" role="status" class="mt-2 mb-0 small">Berkas diperiksa di perangkat ini.</p>
                <a class="btn btn-sm btn-outline-secondary mt-3" download href="{{ asset('modul-sop-pdf/contoh/'.$uuid.'.pdf') }}">Unduh PDF contoh revisi ini</a>
            </div>
        </section>
        @push('scripts')
            <script defer src="{{ asset('modul-sop-pdf/verify-pdf.js') }}"></script>
        @endpush
    @endif
@endsection
