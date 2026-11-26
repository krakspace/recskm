@extends('layouts.app')

@section('title', 'Daftar SOP')
@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><h1>Daftar SOP</h1><p class="text-muted mb-0">{{ $user->name }} · {{ $user->getRoleNames()->implode(', ') }} · {{ $user->organization_code }}</p></div>
        @if ($user->can('sop.draft'))
            <form action="{{ route('sim.create') }}" method="post">@csrf
                <button class="btn btn-primary">Buat draf SOP</button>
            </form>
        @endif
    </div>
    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-3">Dokumen</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($documents as $doc)
                    <tr><td class="ps-3"><strong>{{ $doc->sop_title }}</strong><div class="text-muted small">{{ $doc->sop_code }}</div></td>
                        <td>{{ $doc->status }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('sim.show', $doc->id) }}">Buka SOP</a></td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada SOP yang tersedia untuk akun ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
