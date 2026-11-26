<div class="row g-4 align-items-start">
    <div class="col-12 col-xl-6">
        <section id="editorCard" class="card sop-card shadow-sm" aria-labelledby="editorTitle">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="sop-icon-badge">
                        <i data-icon="edit"></i>
                    </span>
                    <h2 id="editorTitle" class="h6 fw-bold mb-0">{{ $editorTitle ?? 'Editor Formulir SOP' }}</h2>
                    <span id="activeDocBadge" class="badge text-bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                        Sedang Diedit
                    </span>
                </div>
                <span id="save" class="sop-save-status" role="status"></span>
            </div>

            <div class="card-body p-3 p-md-4">
                <div id="steps" class="sop-steps mb-4" aria-label="Bagian formulir">
                    <button type="button" data-step="0">
                        <i data-icon="id"></i>
                        <span>Identitas</span>
                    </button>
                    <button type="button" data-step="1">
                        <i data-icon="list"></i>
                        <span>Isi SOP</span>
                    </button>
                </div>

                <form id="fields" novalidate></form>
            </div>

            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span id="stepLabel" class="small text-muted fw-medium"></span>
                <div class="d-flex align-items-center gap-2">
                    <button id="prevStep" type="button" class="btn btn-outline-secondary btn-sm px-3" hidden>
                        Kembali
                    </button>
                    <button id="next" type="button" class="btn btn-primary btn-sm px-3"></button>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-6">
        @include('partials.sop.preview-card')
    </div>
</div>
