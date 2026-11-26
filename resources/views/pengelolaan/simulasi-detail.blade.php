@extends('layouts.app')

@section('title', $document->sop_title)
@section('content')
    @include('partials.sop.assets')
    <script>window.simulationDraft = {{ Js::from($data) }};</script>
    <a class="btn btn-sm btn-outline-secondary mb-3" href="{{ route('sim.index') }}">Kembali ke daftar SOP</a>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    <div class="sop-module" id="sop-module-root" data-page-mode="{{ $editable ? 'simulation_edit' : 'simulation_read' }}"
         data-revision="{{ $revision->id }}" data-version="{{ hash('sha256', $revision->content) }}"
         data-token="{{ csrf_token() }}" data-save-url="{{ route('sim.save', $document->id) }}"
         data-pdf-lib-url="{{ asset('modul-sop-pdf/assets/pdf-lib.min.js') }}">
        <div class="card sop-card shadow-sm mb-4"><div class="card-body sop-page-header">
            <h1>{{ $document->sop_title }}</h1>
            <p class="text-muted">{{ $document->sop_code }} · Revisi {{ $revision->revision_number }} · {{ $document->status }} · {{ $user->name }}</p>
            @if ($editable)
                <button id="saveDraftBtn" class="btn btn-outline-primary">Simpan draf</button>
                <button id="submitSopBtn" class="btn btn-primary">Ajukan SOP</button>
            @elseif ($document->status === 'REVISION_REQUIRED' && $document->created_by === $user->id && $user->can('sop.draft'))
                <form method="post" action="{{ route('sim.revise', $document->id) }}">@csrf
                    <input type="hidden" name="revision" value="{{ $revision->id }}">
                    <button class="btn btn-primary">Mulai revisi {{ $revision->revision_number + 1 }}</button>
                </form>
            @elseif ($document->status === 'PUBLISHED')
                <a href="{{ route('sim.pdf', $document->id) }}" class="btn btn-primary">Unduh PDF final</a>
            @endif
        </div></div>
        @if ($revision->change_notes)
            <p class="alert alert-warning"><strong>Catatan revisi</strong> · {{ $revision->change_notes }}</p>
        @endif
        @if ($editable)
            @include('partials.sop.editor-preview-cards')
        @else
            @include('partials.sop.preview-card')
        @endif
    </div>
    @include('partials.sop.dialogs')
    @if ($canReview)
        <form class="card card-body shadow-sm mt-4" action="{{ route('sim.review', $document->id) }}" method="post">@csrf
            <h2 class="h5">Keputusan pemeriksa</h2>
            <input type="hidden" name="revision" value="{{ $revision->id }}">
            <label for="reviewNote">Catatan perbaikan (wajib jika dikembalikan)</label>
            <textarea id="reviewNote" name="note" maxlength="3000" class="form-control my-2">{{ old('note') }}</textarea>
            <div><button class="btn btn-primary" name="decision" value="setuju">Setujui</button>
            <button class="btn btn-outline-danger" name="decision" value="tolak">Kembalikan untuk revisi</button></div>
        </form>
    @endif
    @if ($canFinalize)
        <form id="finalizeForm" class="card card-body shadow-sm mt-4" action="{{ route('sim.finalize', $document->id) }}" method="post" enctype="multipart/form-data">@csrf
            <h2 class="h5">Finalisasi Direktur</h2>
            <input type="hidden" name="revision" value="{{ $revision->id }}">
            <label for="finalPassword">Konfirmasi kata sandi untuk menerbitkan PDF revisi ini</label>
            <input id="finalPassword" type="password" name="password" required autocomplete="current-password" class="form-control my-2">
            <input type="file" name="pdf" hidden>
            <div><button class="btn btn-primary">Finalisasi dan terbitkan PDF</button></div>
            <span role="status" class="small mt-2"></span>
        </form>
        @push('scripts')
        <script>
            const finalizeForm = document.getElementById('finalizeForm');
            finalizeForm.addEventListener('submit', async event => {
                event.preventDefault();
                const button = finalizeForm.querySelector('button');
                if (button.disabled) return;
                button.disabled = true;
                const status = finalizeForm.querySelector('[role="status"]');
                status.textContent = 'Menyiapkan PDF final…';
                try {
                    const pdf = await window.SopForm.createPdf();
                    const files = new DataTransfer();
                    files.items.add(new File([pdf], 'sop.pdf', { type: 'application/pdf' }));
                    finalizeForm.elements.pdf.files = files.files;
                    finalizeForm.submit();
                } catch {
                    status.textContent = 'PDF gagal dibuat. Coba lagi.';
                    button.disabled = false;
                }
            });
        </script>
        @endpush
    @endif
    @if ($history->isNotEmpty())
        <div class="card card-body shadow-sm mt-4"><h2 class="h5">Riwayat pemeriksaan</h2><div class="table-responsive">
            <table class="table table-sm"><thead><tr><th>Revisi</th><th>Pemeriksa</th><th>Keputusan</th><th>Catatan</th></tr></thead><tbody>
                @foreach ($history as $step)
                    <tr><td>{{ $step->revision_number }}</td><td>{{ $step->name }} · {{ $step->role }}</td><td>{{ $step->decision ?? 'Belum diputuskan' }}</td><td>{{ $step->note }}</td></tr>
                @endforeach
            </tbody></table>
        </div></div>
    @endif
@endsection
