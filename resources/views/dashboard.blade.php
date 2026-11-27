@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $quickLinks = [
            'pengelolaan.draf-sop' => ['Draf SOP', 'Susun SOP baru atau lanjutkan draf yang tersimpan.'],
            'pengelolaan.dalam-pengajuan' => ['Dalam Pengajuan', 'Pantau SOP yang sedang diajukan untuk persetujuan.'],
            'pengelolaan.perlu-revisi' => ['Perlu Revisi', 'Perbaiki SOP yang dikembalikan untuk revisi.'],
            'pemeriksaan.antrean-supervisor' => ['Antrean Supervisor', 'Periksa SOP yang menunggu tinjauan supervisor.'],
            'pustaka.sop-berlaku' => ['SOP Berlaku', 'Cari SOP yang berlaku dan buka dokumennya.'],
            'verifikasi.cek-status-sop' => ['Cek Status SOP', 'Lihat status dan riwayat versi dokumen.'],
        ];

        if (Auth::guard('web')->check()) {
            $quickLinks = array_filter($quickLinks, fn ($page) => \App\Http\Middleware\EnsurePageAccess::allows(request(), $page), ARRAY_FILTER_USE_KEY);
        }
    @endphp

    <div class="dashboard">
        <header class="dashboard-intro">
            <h1>Beranda</h1>
            <p>Kelola, tinjau, dan temukan prosedur operasional standar rumah sakit.</p>
        </header>

        @if ($quickLinks !== [])
            <section class="dashboard-shortcuts" aria-labelledby="dashboard-shortcuts-heading">
                <h2 id="dashboard-shortcuts-heading">Akses cepat</h2>
                <ul>
                    @foreach ($quickLinks as $page => [$label, $description])
                        <li>
                            <a class="dashboard-shortcut" href="{{ route($page) }}">
                                <span>
                                    <strong>{{ $label }}</strong>
                                    <span class="dashboard-shortcut-description">{{ $description }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @else
            <p class="dashboard-empty">Hubungi administrator jika menu SOP yang Anda perlukan belum tersedia.</p>
        @endif
    </div>
@endsection
