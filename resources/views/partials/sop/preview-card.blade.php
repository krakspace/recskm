<section class="card sop-card shadow-sm" aria-labelledby="previewTitle">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="sop-icon-badge preview-badge">
                <i data-icon="eye"></i>
            </span>
            <h2 id="previewTitle" class="h6 fw-bold mb-0">Pratinjau PDF</h2>
        </div>
        <button
            id="expand"
            type="button"
            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
            aria-label="Perbesar pratinjau"
            title="Perbesar pratinjau PDF"
        >
            <i data-icon="expand"></i>
            <span class="d-none d-sm-inline">Layar Penuh</span>
        </button>
    </div>

    <div class="card-body sop-tray">
        <div id="paper" class="sop-paper"></div>
    </div>

    <div class="card-footer d-flex align-items-center justify-content-between gap-2">
        <span class="small text-muted d-inline-flex align-items-center gap-2">
            <i data-icon="file"></i>
            <span>Letter · Potret</span>
        </span>
        <div class="d-inline-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" data-page="-1" aria-label="Halaman sebelumnya">
                <i data-icon="left"></i>
            </button>
            <span class="sop-pagecount small fw-semibold text-muted"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" data-page="1" aria-label="Halaman berikutnya">
                <i data-icon="right"></i>
            </button>
        </div>
    </div>
</section>
