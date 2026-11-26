{{-- Full PDF Preview Dialog --}}
<dialog id="large" class="sop-dialog sop-dialog-lg" aria-label="Pratinjau PDF">
    <div class="sop-dialog-head">
        <h2 id="pdfModalTitle" class="h6 fw-bold mb-0 text-truncate">Pratinjau Dokumen PDF</h2>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button id="modalDownloadPdfBtn" type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i data-icon="download"></i>
                <span>Unduh PDF</span>
            </button>
            <a id="pdfTab" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" hidden>
                Buka di tab baru
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary sop-dialog-close" aria-label="Tutup">
                <i data-icon="x"></i>
            </button>
        </div>
    </div>
    <p id="pdfStatus" class="p-4 mb-0 text-muted" role="status"></p>
    <iframe id="pdfViewer" class="sop-dialog-pdf-viewer" title="Dokumen SOP dalam format PDF" hidden></iframe>
</dialog>

{{-- Help Dialog --}}
<dialog id="helpDialog" class="sop-dialog" aria-labelledby="helpTitle">
    <div class="sop-dialog-head">
        <h2 id="helpTitle" class="h6 fw-bold mb-0">Panduan Pengelolaan SOP</h2>
        <button type="button" class="btn btn-sm btn-outline-secondary sop-dialog-close" aria-label="Tutup">
            <i data-icon="x"></i>
        </button>
    </div>
    <div class="p-4">
        <ol class="ps-3 mb-3 d-grid gap-2 small">
            <li>
                <strong>Susun di Draf SOP.</strong> Isi bagian <em>Identitas</em> dan <em>Isi SOP</em> di bagian atas halaman, lalu klik <em>Simpan Draf</em> untuk menyimpannya ke tabel draf di bagian bawah.
            </li>
            <li>
                <strong>Ajukan Persetujuan.</strong> Setelah seluruh bagian lengkap dan pratinjau PDF sesuai, klik <em>Ajukan SOP</em> agar dokumen masuk ke antrean <strong>Dalam Pengajuan</strong>.
            </li>
            <li>
                <strong>Tindak Lanjut Revisi.</strong> Jika pemeriksa mengembalikan dokumen dengan catatan koreksi, perbaiki langsung melalui halaman <strong>Perlu Revisi</strong> lalu klik <em>Ajukan Ulang</em>.
            </li>
        </ol>
        <div class="alert alert-light border small mb-0">
            Seluruh perubahan formulir otomatis menyesuaikan pembagian halaman pada <strong>Pratinjau PDF</strong> kertas Letter.
        </div>
    </div>
</dialog>

@if (!empty($includeRevisionRequestModal))
    <dialog id="decisionDialog" class="sop-dialog" aria-labelledby="decisionTitle">
        <div class="sop-dialog-head"><h2 id="decisionTitle" class="h6 fw-bold mb-0"></h2></div>
        <div class="p-4">
            <p id="decisionSummary"></p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary sop-dialog-close">Batal</button>
                <button id="confirmDecision" type="button" class="btn btn-primary"></button>
            </div>
        </div>
    </dialog>
    <dialog id="requestRevisionDialog" class="sop-dialog" aria-labelledby="requestRevisionTitle">
        <div class="sop-dialog-head">
            <h2 id="requestRevisionTitle" class="h6 fw-bold mb-0">Kembalikan untuk Revisi</h2>
            <button type="button" class="btn btn-sm btn-outline-secondary sop-dialog-close" aria-label="Tutup">
                <i data-icon="x"></i>
            </button>
        </div>
        <div class="p-4">
            <div class="mb-3">
                <label for="revisionReviewerInput" class="form-label fw-semibold small">Pemeriksa / Tahap</label>
                <input
                    id="revisionReviewerInput"
                    type="text"
                    class="form-control"
                    readonly
                >
            </div>
            <div class="mb-3">
                <label for="revisionNoteInput" class="form-label fw-semibold small">
                    Catatan Pemeriksa <span class="text-danger">*</span>
                </label>
                <textarea
                    id="revisionNoteInput"
                    rows="4"
                    class="form-control"
                    placeholder="Tuliskan bagian yang perlu diperbaiki oleh penyusun SOP..."
                ></textarea>
                <div class="invalid-feedback">Catatan revisi wajib diisi sebelum mengembalikan dokumen.</div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary sop-dialog-close">Batal</button>
                <button id="confirmRequestRevisionBtn" type="button" class="btn btn-warning fw-semibold">
                    Kirim ke Perlu Revisi
                </button>
            </div>
        </div>
    </dialog>
@endif

<div id="feedback" role="status" class="sop-toast" hidden></div>
