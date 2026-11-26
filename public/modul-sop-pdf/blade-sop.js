(() => {
  const root = document.getElementById("sop-module-root");
  if (!root) return;

  window.SOP_PDF_LIB_URL = root.dataset.pdfLibUrl || "/modul-sop-pdf/assets/pdf-lib.min.js";

  const pageMode = root.dataset.pageMode || "draft"; // "draft" | "in_review" | "needs_revision"
  const drafSopUrl = root.dataset.drafSopUrl || "/pengelolaan/draf-sop";
  const databaseWorkspace = pageMode.startsWith("simulation_");
  const reviewStages = ["Kepala Unit", "Atasan", "Direktur"];
  const STORAGE_KEY = "sysman.sop.records.v2";
  const ORG_NAME = typeof FIXED_ORGANIZATION === "string" ? FIXED_ORGANIZATION : "PT Krakatau Medika";

  const glyphs = {
    help: '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.7 2.2q-1.2.7-1.2 2.1m0 3h.01"/>',
    download: '<path d="M12 3v12m-5-5 5 5 5-5M4 16v4h16v-4"/>',
    file: '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6m-6 3h5"/>',
    edit: '<path d="M12 4H5v16h14v-8M10 14l1-4 7-7 3 3-7 7z"/>',
    id: '<rect x="2.5" y="5" width="19" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M5.5 15q3-4 6 0M14 10h4m-4 4h3"/>',
    list: '<path d="M9 6h11M9 12h11M9 18h11M4 6h.5M4 12h.5M4 18h.5"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="2.7"/>',
    expand: '<path d="M4 9V4h5m6 0h5v5m0 6v5h-5M9 20H4v-5"/>',
    left: '<path d="m14 6-6 6 6 6"/>',
    right: '<path d="m10 6 6 6-6 6"/>',
    x: '<path d="m6 6 12 12M18 6 6 18"/>',
    check: '<path d="m8.5 12 2.4 2.5 4.8-5"/><circle cx="12" cy="12" r="9"/>',
    save: '<path d="M5 4h11l3 3v13H5z"/><path d="M8 4v5h7V4M8 20v-6h8v6"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    trash: '<path d="M4 7h16M9 7V4h6v3m-7 3v7m4-7v7m4-7v7M6 7l1 13h10l1-13"/>',
    send: '<path d="m21 3-6.5 18-3.5-7-7-3.5z"/><path d="M21 3 11 10.5"/>',
    alert: '<path d="M12 3 2.5 20h19z"/><path d="M12 9v5m0 3.2h.01"/>',
    folder: '<path d="M3 6.5h6l2 2.5h10v9.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    history: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5m4-1v5l3 2"/>'
  };

  const get = (id) => document.getElementById(id);
  function icon(name) {
    return '<svg class="sop-ico" viewBox="0 0 24 24" aria-hidden="true">' + (glyphs[name] || "") + "</svg>";
  }

  function hydrateIcons(scope = document) {
    scope.querySelectorAll("[data-icon]").forEach((el) => {
      el.outerHTML = icon(el.dataset.icon);
    });
  }
  hydrateIcons(root);
  document.querySelectorAll(".sop-dialog").forEach((d) => hydrateIcons(d));

  function normalizeRevision(rev) {
    const num = parseInt(String(rev ?? "1"), 10);
    return Number.isFinite(num) && num >= 1 ? String(num) : "1";
  }

  function nextRevisionNumber(rev) {
    return String((parseInt(normalizeRevision(rev), 10) || 1) + 1);
  }

  function defaultData(revision = "1") {
    return {
      ...example,
      revision: normalizeRevision(revision),
      organization: ORG_NAME,
      procedure: plainProcedure(example.procedure, true)
    };
  }

  function blankData(revision = "1") {
    return {
      title: "",
      number: "",
      revision: normalizeRevision(revision),
      unit: "",
      organization: ORG_NAME,
      definition: "",
      purpose: "",
      policy: "",
      procedure: plainProcedure(""),
      related: ""
    };
  }

  function sanitizeData(raw) {
    if (!raw || typeof raw !== "object") return defaultData("1");
    const clean = blankData(raw.revision);
    clean.organization = ORG_NAME;
    for (const section of sections) {
      for (const [id, , , , max] of section.fields) {
        if (id !== "procedure" && typeof raw[id] === "string") {
          clean[id] = raw[id].slice(0, max || 30000);
        }
      }
    }
    if (typeof raw.procedure === "string") {
      clean.procedure = plainProcedure(raw.procedure);
    } else if (Array.isArray(raw.procedure?.ops) && raw.procedure.ops.every((op) => typeof op?.insert === "string")) {
      clean.procedure = structuredClone(raw.procedure);
    }
    return clean;
  }

  function makeId() {
    return "sop-" + Date.now().toString(36) + "-" + Math.random().toString(36).slice(2, 6);
  }

  function buildSeedRecords() {
    const now = Date.now();
    return [
      {
        id: "sop-seed-draft-1",
        status: "draft",
        parentId: null,
        parentRevision: null,
        childDraftId: null,
        updatedAt: new Date(now - 15 * 60 * 1000).toISOString(),
        submittedAt: null,
        approvalStage: null,
        revisionNote: null,
        data: defaultData("1")
      },
      {
        id: "sop-seed-review-1",
        status: "in_review",
        parentId: null,
        parentRevision: null,
        childDraftId: null,
        updatedAt: new Date(now - 3 * 3600 * 1000).toISOString(),
        submittedAt: new Date(now - 3 * 3600 * 1000).toISOString(),
        approvalStage: reviewStages[0],
        revisionNote: null,
        data: {
          title: "Pengelolaan Hak Akses Pengguna Sistem Informasi Rumah Sakit",
          number: "Pro_KM.75.008",
          revision: "1",
          unit: "Information, Communication & Technology",
          organization: ORG_NAME,
          definition: "Prosedur pemberian, perubahan, dan pencabutan hak akses pengguna pada Sistem Informasi Rumah Sakit.",
          purpose: "1. Menjamin keamanan akses data pasien dan operasional.\n2. Memastikan setiap akun memiliki otorisasi sesuai jabatan.",
          policy: "SK Direktur tentang Kebijakan Keamanan Informasi PT Krakatau Medika.",
          procedure: plainProcedure(
            "Unit Pemohon:\n1. Ajukan formulir permintaan hak akses yang disetujui Kepala Unit.\n2. Serahkan formulir kepada tim ICT.\n\nTim ICT:\n1. Verifikasi kesesuaian peran dan kewenangan pengguna.\n2. Konfigurasi akun pada aplikasi sesuai matriks akses.\n3. Dokumentasikan berita acara serah terima akun.",
            true
          ),
          related: "1. Seluruh Unit Kerja\n2. Bagian SDM"
        }
      },
      {
        id: "sop-seed-revision-1",
        status: "needs_revision",
        parentId: null,
        parentRevision: null,
        childDraftId: null,
        updatedAt: new Date(now - 6 * 3600 * 1000).toISOString(),
        submittedAt: new Date(now - 24 * 3600 * 1000).toISOString(),
        approvalStage: "Dikembalikan oleh Komite Mutu",
        revisionNote: {
          reviewer: "Komite Mutu & Manajemen Risiko",
          returnedAt: new Date(now - 6 * 3600 * 1000).toISOString(),
          note: "Mohon lengkapi batas waktu (SLA) pemulihan cadangan data pada langkah ke-3 di bagian Prosedur, serta tambahkan Unit Rekam Medis pada kolom Unit Terkait."
        },
        data: {
          title: "Pencadangan (Backup) dan Pemulihan Basis Data",
          number: "Pro_KM.75.012",
          revision: "1",
          unit: "Information, Communication & Technology",
          organization: ORG_NAME,
          definition: "Tata cara pelaksanaan backup berkala dan uji pemulihan (restore) basis data aplikasi.",
          purpose: "1. Mencegah kehilangan data kritikal operasional.\n2. Menjamin ketersediaan data saat terjadi gangguan sistem.",
          policy: "SK Direktur No. 94/Dir-KM/Kpts/X/2022 tentang Pedoman Pelayanan ICT.",
          procedure: plainProcedure(
            "Administrator Basis Data:\n1. Pastikan jadwal backup harian otomatis berjalan pada server penyimpanan cadangan.\n2. Periksa log keberhasilan backup setiap pagi.\n3. Lakukan uji pemulihan (restore test) secara berkala pada lingkungan pengujian.",
            true
          ),
          related: "1. Unit Infrastruktur TI"
        }
      }
    ];
  }

  let lastStoredSnapshot = null;

  function loadRecords() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      lastStoredSnapshot = raw;
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          return parsed
            .filter(
              (r) =>
                r &&
                typeof r.id === "string" &&
                ["draft", "in_review", "needs_revision", "superseded", "approved", "signed"].includes(r.status)
            )
            .map((r) => ({
              id: r.id,
              status: r.status,
              backup: r.backup ?? null,
              parentId: r.parentId || null,
              parentRevision: r.parentRevision || null,
              childDraftId: r.childDraftId || null,
              updatedAt: r.updatedAt || new Date().toISOString(),
              submittedAt: r.submittedAt || null,
              approvalStage: r.approvalStage || null,
              decisions: Array.isArray(r.decisions) ? r.decisions : [],
              revisionNote: r.revisionNote || null,
              data: sanitizeData(r.data)
            }));
        }
      }
    } catch {}
    const seeded = buildSeedRecords();
    saveRecords(seeded);
    return seeded;
  }

  function saveRecords(list) {
    try {
      if (localStorage.getItem(STORAGE_KEY) !== lastStoredSnapshot) return false;
      const snapshot = JSON.stringify(list);
      localStorage.setItem(STORAGE_KEY, snapshot);
      lastStoredSnapshot = snapshot;
      return true;
    } catch {
      return false;
    }
  }

  function formatTimestamp(iso) {
    if (!iso) return "—";
    try {
      return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      }).format(new Date(iso));
    } catch {
      return "—";
    }
  }

  let records = databaseWorkspace ? [] : loadRecords();
  window.addEventListener("storage", event => {
    if (databaseWorkspace) return;
    if (event.key !== STORAGE_KEY && event.key !== null) return;
    const previous = findRecord(activeRecordId);
    records = loadRecords();
    if (pageMode === "draft") {
      const current = findRecord(activeRecordId);
      if (previous && (current?.status !== "draft" || previous.updatedAt !== current.updatedAt)) {
        const next = current?.status === "draft" ? current : recordsByStatus("draft")[0];
        if (next) loadRecordIntoWorkspace(next, false);
        else startFreshDraft(false);
        notify("Tampilan disesuaikan dengan perubahan draf dari tab lain.");
      }
      renderDraftTable();
      return;
    }
    document.querySelectorAll("dialog[open]").forEach(dialog => dialog.close());
    if (pageMode === "needs_revision") renderNeedsRevisionTable();
    else renderInReviewTable();
  });
  let activeRecordId = null;
  let data = defaultData("1");
  let modalPreviewData = null;
  let step = 0;
  let pageIndex = 0;
  let pages = [];
  let timer;
  let feedbackTimer;
  let procedureField = null;
  let quillInstance = null;
  let pendingRevisionTargetId = null;
  let searchQuery = "";
  let draftListFilter = "active"; // "active" | "history"

  function recordsByStatus(status) {
    return records.filter((r) => r.status === status);
  }

  function findRecord(id) {
    return records.find((r) => r.id === id) || null;
  }

  function notify(message) {
    const fb = get("feedback");
    if (!fb) return;
    fb.textContent = message;
    fb.hidden = false;
    clearTimeout(feedbackTimer);
    feedbackTimer = setTimeout(() => {
      fb.hidden = true;
    }, 4300);
  }

  function updateActiveDocBadge() {
    const badge = get("activeDocBadge");
    if (!badge) return;
    const rev = normalizeRevision(data.revision);
    if (!activeRecordId) {
      badge.textContent = "Draf Baru · Revisi " + rev;
      badge.className = "badge text-bg-success-subtle text-success-emphasis border border-success-subtle";
    } else {
      const prefix = data.number?.trim() ? data.number.trim() + " · " : "";
      badge.textContent = prefix + "Revisi " + rev;
      badge.className = "badge text-bg-primary-subtle text-primary-emphasis border border-primary-subtle";
    }
  }

  function updateDraftRevisionContextBanner() {
    const banner = get("draftRevisionBanner");
    if (!banner) return;
    const currentRecord = activeRecordId ? findRecord(activeRecordId) : null;
    if (!currentRecord || (!currentRecord.parentId && !currentRecord.revisionNote)) {
      banner.hidden = true;
      return;
    }

    banner.hidden = false;
    const titleEl = get("draftRevisionHeading");
    const metaEl = get("draftRevisionMeta");
    const noteEl = get("draftRevisionNoteText");
    const compareBtn = get("viewParentPdfBtn");
    const parentRec = currentRecord.parentId ? findRecord(currentRecord.parentId) : null;

    if (titleEl) {
      titleEl.textContent =
        "Revisi ke-" +
        normalizeRevision(currentRecord.data.revision) +
        (currentRecord.parentRevision ? " (Turunan dari Revisi ke-" + currentRecord.parentRevision + ")" : "");
    }
    if (metaEl) {
      const reviewer = currentRecord.revisionNote?.reviewer || "Riwayat Revisi Sebelumnya";
      const time = currentRecord.revisionNote?.returnedAt || currentRecord.updatedAt;
      metaEl.textContent = reviewer + " · " + formatTimestamp(time);
    }
    if (noteEl) {
      noteEl.textContent =
        currentRecord.revisionNote?.note ||
        "Draf baru ini dibuat dari versi sebelumnya. Dokumen versi awal tetap tersimpan utuh di tab Riwayat Revisi (Audit).";
    }
    if (compareBtn) {
      if (parentRec) {
        compareBtn.hidden = false;
        compareBtn.textContent = "Lihat PDF Revisi " + normalizeRevision(parentRec.data.revision);
        compareBtn.onclick = () => openPdfModalForData(parentRec.data);
      } else {
        compareBtn.hidden = true;
      }
    }
  }

  function displayPage() {
    const paper = get("paper");
    if (!paper || !pages.length) return;
    pageIndex = Math.min(Math.max(0, pageIndex), pages.length - 1);
    paper.replaceChildren(renderSheet(pages[pageIndex]));
    root.querySelectorAll(".sop-pagecount").forEach((el) => {
      el.textContent = pageIndex + 1 + " / " + pages.length;
    });
    root.querySelectorAll("[data-page]").forEach((el) => {
      el.disabled = el.dataset.page === "-1" ? pageIndex === 0 : pageIndex === pages.length - 1;
    });
  }

  function refreshPreview() {
    data.organization = ORG_NAME;
    data.revision = normalizeRevision(data.revision);
    pages = createLayout(data);
    displayPage();
  }

  function clearFieldError(element) {
    element.removeAttribute("aria-invalid");
    element.removeAttribute("aria-describedby");
    element.classList.remove("is-invalid");
    const container = element.closest(".sop-field");
    if (container) {
      container.querySelector(".richtext")?.classList.remove("is-invalid");
      container.querySelector(".invalid-feedback")?.remove();
    }
  }

  function persistActiveEdit() {
    if (databaseWorkspace) {
      get("save").textContent = "Perubahan belum disimpan";
      return;
    }
    const now = new Date().toISOString();
    data.organization = ORG_NAME;
    data.revision = normalizeRevision(data.revision);

    if (activeRecordId) {
      const idx = records.findIndex((r) => r.id === activeRecordId);
      if (idx >= 0) {
        records[idx] = {
          ...records[idx],
          updatedAt: now,
          data: structuredClone(data)
        };
      }
    } else if (pageMode === "draft") {
      activeRecordId = makeId();
      records.unshift({
        id: activeRecordId,
        status: "draft",
        parentId: null,
        parentRevision: null,
        childDraftId: null,
        updatedAt: now,
        submittedAt: null,
        approvalStage: null,
        revisionNote: null,
        data: structuredClone(data)
      });
    }

    const ok = saveRecords(records);
    const saveEl = get("save");
    if (saveEl) {
      saveEl.innerHTML = ok ? icon("check") + "<span>Tersimpan otomatis</span>" : "Penyimpanan lokal nonaktif";
    }
    updateActiveDocBadge();
  }

  function changed(id, value, element) {
    data[id] = value;
    clearFieldError(element);
    persistActiveEdit();
    clearTimeout(timer);
    timer = setTimeout(() => {
      refreshPreview();
      renderDraftTable();
    }, 120);
  }

  function mountProcedure(wrap, label) {
    if (procedureField && quillInstance) {
      quillInstance.setContents(data.procedure, "silent");
      return procedureField;
    }
    const editorBox = document.createElement("div");
    editorBox.className = "richtext";
    const host = document.createElement("div");
    editorBox.append(host);
    label.id = "procedure-label";
    wrap.append(label, editorBox);

    quillInstance = new Quill(host, {
      theme: "snow",
      formats: ["bold", "italic", "underline", "list"],
      modules: {
        toolbar: [["bold", "italic", "underline"], [{ list: "ordered" }, { list: "bullet" }], ["clean"]]
      }
    });
    quillInstance.root.id = "procedure";
    quillInstance.root.setAttribute("role", "textbox");
    quillInstance.root.setAttribute("aria-labelledby", label.id);
    quillInstance.root.setAttribute("aria-multiline", "true");
    quillInstance.root.setAttribute("aria-required", "true");
    label.onclick = () => quillInstance.focus();

    const labels = {
      bold: "Tebal",
      italic: "Miring",
      underline: "Garis bawah",
      ordered: "Daftar bernomor",
      bullet: "Daftar berpoin",
      clean: "Hapus format"
    };
    for (const button of editorBox.querySelectorAll(".ql-toolbar button")) {
      const name = labels[button.value || button.classList[0].slice(3)];
      if (name) {
        button.title = name;
        button.setAttribute("aria-label", name);
      }
    }
    quillInstance.setContents(data.procedure, "silent");
    quillInstance.on("text-change", () =>
      changed("procedure", { ops: quillInstance.getContents().ops }, quillInstance.root)
    );
    procedureField = wrap;
    return wrap;
  }

  function renderSystemIdentityStrip() {
    const box = document.createElement("div");
    box.className = "col-12";
    const inner = document.createElement("div");
    inner.className = "row g-2 p-2 rounded-3 bg-light border mx-0";

    const colOrg = document.createElement("div");
    colOrg.className = "col-12 col-sm-7";
    colOrg.innerHTML =
      '<div class="text-muted" style="font-size:0.75rem;">Instansi (Tetap)</div>' +
      '<div class="fw-semibold small text-dark" id="fixedOrgDisplay"></div>';
    colOrg.querySelector("#fixedOrgDisplay").textContent = ORG_NAME;

    const colRev = document.createElement("div");
    colRev.className = "col-12 col-sm-5";
    colRev.innerHTML =
      '<div class="text-muted" style="font-size:0.75rem;">Nomor Revisi (Otomatis Sistem)</div>' +
      '<div class="fw-semibold small text-primary" id="systemRevisionDisplay"></div>';
    colRev.querySelector("#systemRevisionDisplay").textContent = "Revisi ke-" + normalizeRevision(data.revision);

    inner.append(colOrg, colRev);
    box.append(inner);
    return box;
  }

  function switchStep(i, focus = false) {
    const form = get("fields");
    if (!form) return;
    step = i;
    const section = sections[i];
    form.replaceChildren();

    const heading = document.createElement("h3");
    heading.className = "h6 fw-bold mb-3 text-dark";
    heading.textContent = section.title;

    const grid = document.createElement("div");
    grid.className = "row g-3";
    form.append(heading, grid);

    if (i === 0) {
      grid.append(renderSystemIdentityStrip());
    }

    for (const [id, name, type, required, max] of section.fields) {
      const isHalf = ["number", "unit"].includes(id);
      const wrap = document.createElement("div");
      wrap.className = (isHalf ? "col-12 col-md-6" : "col-12") + " sop-field";

      const label = document.createElement("label");
      label.className = "form-label fw-semibold mb-1";
      label.htmlFor = id;
      label.textContent = name;
      if (required) {
        const star = document.createElement("em");
        star.textContent = " *";
        star.setAttribute("aria-hidden", "true");
        label.append(star);
      }

      if (type === "quill") {
        grid.append(mountProcedure(wrap, label));
        continue;
      }

      const el = document.createElement(type === "textarea" ? "textarea" : "input");
      el.className = "form-control";
      if (el.tagName === "INPUT") el.type = type;
      else el.rows = step === 1 ? 3 : 2;
      el.id = id;
      el.name = id;
      el.required = required;
      el.maxLength = max || 30000;
      el.value = data[id] || "";
      el.addEventListener("input", () => changed(id, el.value, el));

      wrap.append(label, el);
      grid.append(wrap);
    }

    root.querySelectorAll("[data-step]").forEach((b) => {
      if (+b.dataset.step === i) b.setAttribute("aria-current", "step");
      else b.removeAttribute("aria-current");
    });

    const stepLabel = get("stepLabel");
    if (stepLabel) stepLabel.textContent = "Bagian " + (i + 1) + " dari " + sections.length;

    const prevBtn = get("prevStep");
    if (prevBtn) prevBtn.hidden = i === 0;

    const nextBtn = get("next");
    if (nextBtn) {
      nextBtn.textContent = i === 0 ? "Lanjut ke Isi SOP" : "Simpan Draf";
    }

    if (focus) {
      form.querySelector("input, textarea")?.focus();
    }
  }

  function valid() {
    for (const [i, s] of sections.entries()) {
      for (const [id, name, , required] of s.fields) {
        const val = id === "procedure" ? procedureText(data.procedure) : data[id] || "";
        if (required && !val.trim()) {
          switchStep(i);
          const el = get(id);
          if (!el) return false;
          el.setAttribute("aria-invalid", "true");
          if (id === "procedure") {
            el.closest(".richtext")?.classList.add("is-invalid");
          } else {
            el.classList.add("is-invalid");
          }
          const msg = document.createElement("div");
          msg.className = "invalid-feedback d-block";
          msg.id = id + "-error";
          msg.textContent = name + " wajib diisi.";
          el.closest(".sop-field")?.append(msg);
          el.setAttribute("aria-describedby", msg.id);
          el.focus();
          return false;
        }
      }
    }
    return true;
  }

  function loadRecordIntoWorkspace(record, focus = false) {
    activeRecordId = record.id;
    data = structuredClone(record.data);
    data.organization = ORG_NAME;
    data.revision = normalizeRevision(data.revision);
    pageIndex = 0;
    if (quillInstance) {
      quillInstance.setContents(data.procedure, "silent");
    }
    if (get("fields")) {
      switchStep(0, focus);
    }
    updateActiveDocBadge();
    updateDraftRevisionContextBanner();
    refreshPreview();
  }

  // Create a new SOP draft record with incremented revision (+1) while preserving the original record intact for audit!
  function createRevisionBranch(sourceRecord) {
    if (sourceRecord.childDraftId) {
      const existingChild = findRecord(sourceRecord.childDraftId);
      if (existingChild && existingChild.status === "draft") {
        return existingChild;
      }
    }

    const now = new Date().toISOString();
    const newRev = nextRevisionNumber(sourceRecord.data.revision);
    const clonedData = structuredClone(sourceRecord.data);
    clonedData.organization = ORG_NAME;
    clonedData.revision = newRev;

    const newRecord = {
      id: makeId(),
      status: "draft",
      parentId: sourceRecord.id,
      parentRevision: normalizeRevision(sourceRecord.data.revision),
      childDraftId: null,
      updatedAt: now,
      submittedAt: null,
      approvalStage: null,
      revisionNote: sourceRecord.revisionNote ? structuredClone(sourceRecord.revisionNote) : null,
      data: clonedData
    };

    const srcIdx = records.findIndex((r) => r.id === sourceRecord.id);
    if (srcIdx >= 0) {
      records[srcIdx] = {
        ...records[srcIdx],
        // If revising an existing draft, archive old draft into 'superseded' audit history;
        // if revising from 'needs_revision', mark childDraftId and move old version to 'superseded' once tracked,
        // or mark 'superseded' immediately so it's locked for audit while the new Rev 2 draft is edited!
        status: "superseded",
        childDraftId: newRecord.id
      };
    }

    records.unshift(newRecord);
    saveRecords(records);
    return newRecord;
  }

  // --- DRAF SOP TABLE (BOTTOM: ACTIVE DRAFTS + AUDIT REVISION HISTORY) ---
  function renderDraftTable() {
    const tbody = get("draftListBody");
    const countBadge = get("draftCountBadge");
    const historyCountBadge = get("historyCountBadge");
    if (!tbody) return;

    const activeDrafts = recordsByStatus("draft");
    const historyItems = records.filter((r) => r.status === "superseded");

    if (countBadge) countBadge.textContent = activeDrafts.length;
    if (historyCountBadge) historyCountBadge.textContent = historyItems.length;

    const sourceList = draftListFilter === "history" ? historyItems : activeDrafts;

    const filtered = sourceList.filter((item) => {
      if (!searchQuery) return true;
      const hay = [item.data.title, item.data.number, item.data.unit, "rev " + item.data.revision]
        .join(" ")
        .toLowerCase();
      return hay.includes(searchQuery);
    });

    tbody.replaceChildren();
    if (!filtered.length) {
      const tr = document.createElement("tr");
      const td = document.createElement("td");
      td.colSpan = 5;
      td.className = "text-center text-muted py-4 small";
      if (draftListFilter === "history") {
        td.textContent = "Belum ada riwayat revisi awal yang diarsipkan.";
      } else {
        td.textContent = activeDrafts.length
          ? "Tidak ada draf yang cocok dengan kata kunci pencarian."
          : "Belum ada draf SOP aktif. Silakan isi formulir di atas lalu klik Simpan Draf.";
      }
      tr.append(td);
      tbody.append(tr);
      return;
    }

    for (const item of filtered) {
      const tr = document.createElement("tr");
      if (draftListFilter === "active" && item.id === activeRecordId) {
        tr.className = "sop-row-active";
      }

      const tdDoc = document.createElement("td");
      tdDoc.className = "ps-3 py-3";
      const docTitle = document.createElement("div");
      docTitle.className = "fw-semibold text-dark";
      docTitle.textContent = item.data.title.trim() || "(Draf Belum Berjudul)";
      const docMeta = document.createElement("div");
      docMeta.className = "small text-muted";
      docMeta.textContent = item.data.number.trim() || "Tanpa Nomor Dokumen";
      tdDoc.append(docTitle, docMeta);

      const tdRev = document.createElement("td");
      tdRev.className = "py-3";
      const revBadge = document.createElement("span");
      const revNum = normalizeRevision(item.data.revision);
      revBadge.className =
        draftListFilter === "history"
          ? "badge text-bg-secondary-subtle text-secondary-emphasis border"
          : revNum === "1"
            ? "badge text-bg-light text-dark border"
            : "badge text-bg-primary-subtle text-primary-emphasis border border-primary-subtle";
      revBadge.textContent = "Revisi " + revNum;
      tdRev.append(revBadge);
      if (item.parentRevision) {
        const lineage = document.createElement("div");
        lineage.className = "text-muted mt-1";
        lineage.style.fontSize = "0.73rem";
        lineage.textContent = "Dari Revisi " + item.parentRevision;
        tdRev.append(lineage);
      }

      const tdUnit = document.createElement("td");
      tdUnit.className = "py-3";
      const uName = document.createElement("div");
      uName.className = "small fw-medium text-dark";
      uName.textContent = item.data.unit.trim() || "—";
      const oName = document.createElement("div");
      oName.className = "small text-muted";
      oName.textContent = ORG_NAME;
      tdUnit.append(uName, oName);

      const tdUpdated = document.createElement("td");
      tdUpdated.className = "py-3 small text-muted text-nowrap";
      tdUpdated.textContent = formatTimestamp(item.updatedAt);

      const tdActions = document.createElement("td");
      tdActions.className = "pe-3 py-3 text-end text-nowrap";
      const group = document.createElement("div");
      group.className = "d-inline-flex gap-1 flex-wrap justify-content-end";

      if (draftListFilter === "history") {
        // Audit / History items are immutable snapshots! Can preview PDF cleanly.
        const pdfBtn = document.createElement("button");
        pdfBtn.type = "button";
        pdfBtn.className = "btn btn-sm btn-outline-primary";
        pdfBtn.innerHTML = icon("eye") + '<span class="ms-1">Pratinjau PDF (Audit)</span>';
        pdfBtn.onclick = () => openPdfModalForData(item.data);
        group.append(pdfBtn);
      } else {
        const editBtn = document.createElement("button");
        editBtn.type = "button";
        editBtn.className =
          item.id === activeRecordId ? "btn btn-sm btn-primary" : "btn btn-sm btn-outline-primary";
        editBtn.innerHTML =
          icon("edit") +
          '<span class="ms-1">' +
          (item.id === activeRecordId ? "Sedang Dibuka" : "Edit") +
          "</span>";
        editBtn.onclick = () => {
          loadRecordIntoWorkspace(item, true);
          renderDraftTable();
          get("editorCard")?.scrollIntoView({ behavior: "smooth", block: "start" });
        };

        const branchBtn = document.createElement("button");
        branchBtn.type = "button";
        branchBtn.className = "btn btn-sm btn-outline-secondary";
        branchBtn.title =
          "Buat revisi baru (Revisi " +
          nextRevisionNumber(item.data.revision) +
          ") dan simpan versi ini ke Riwayat Revisi";
        branchBtn.innerHTML =
          icon("history") + '<span class="ms-1 d-none d-lg-inline">Buat Rev ' + nextRevisionNumber(item.data.revision) + "</span>";
        branchBtn.onclick = () => {
          const nextRec = createRevisionBranch(item);
          loadRecordIntoWorkspace(nextRec, true);
          renderDraftTable();
          get("editorCard")?.scrollIntoView({ behavior: "smooth", block: "start" });
          notify(
            "SOP baru Revisi ke-" +
              nextRec.data.revision +
              " dibuat. Draf sebelumnya tersimpan di tab Riwayat Revisi (Audit)."
          );
        };

        const submitRowBtn = document.createElement("button");
        submitRowBtn.type = "button";
        submitRowBtn.className = "btn btn-sm btn-outline-success";
        submitRowBtn.innerHTML = icon("send") + '<span class="ms-1 d-none d-md-inline">Ajukan</span>';
        submitRowBtn.onclick = () => {
          loadRecordIntoWorkspace(item, false);
          renderDraftTable();
          submitCurrentSop();
        };

        const delBtn = document.createElement("button");
        delBtn.type = "button";
        delBtn.className = "btn btn-sm btn-outline-danger";
        delBtn.title = "Hapus Draf";
        delBtn.setAttribute("aria-label", "Hapus draf " + (item.data.title || ""));
        delBtn.innerHTML = icon("trash");
        delBtn.onclick = () => {
          records = records.filter((r) => r.id !== item.id);
          saveRecords(records);
          if (activeRecordId === item.id) {
            const remaining = recordsByStatus("draft");
            if (remaining.length) {
              loadRecordIntoWorkspace(remaining[0], false);
            } else {
              startFreshDraft(false);
            }
          }
          renderDraftTable();
          notify("Draf SOP berhasil dihapus.");
        };

        group.append(editBtn, branchBtn, submitRowBtn, delBtn);
      }

      tdActions.append(group);
      tr.append(tdDoc, tdRev, tdUnit, tdUpdated, tdActions);
      tbody.append(tr);
    }
  }

  function startFreshDraft(focus = true) {
    activeRecordId = null;
    data = blankData("1");
    pageIndex = 0;
    if (quillInstance) quillInstance.setContents(data.procedure, "silent");
    switchStep(0, focus);
    updateActiveDocBadge();
    updateDraftRevisionContextBanner();
    refreshPreview();
    renderDraftTable();
  }

  const backupUrl = root.dataset.backupUrl;
  const databaseBackup = get("databaseBackup");
  const backupSession = root.dataset.backupSession;
  const serverDrafts = get("serverDrafts");
  const pendingSaves = new Set();

  async function refreshServerList(selectedKey) {
    const response = await fetch(backupUrl, { headers: { Accept: "application/json" }, cache: "no-store" });
    if (!response.ok) throw new Error();
    const list = await response.json();
    serverDrafts.replaceChildren(new Option(list.length ? "Pilih draf tersimpan" : "Belum ada draf di database", ""));
    for (const row of list) {
      serverDrafts.add(new Option(row.title || "Draf tanpa judul", row.draft_key));
    }
    serverDrafts.value = selectedKey || "";
    get("restoreDraft").disabled = !serverDrafts.value;
  }

  if (backupUrl) {
    const status = get("backupStatus");
    databaseBackup.onchange = () => {
      get("backupOptions").hidden = !databaseBackup.checked;
      if (databaseBackup.checked) refreshServerList().catch(() => {
        status.textContent = "Database belum tersedia. Draf tetap bisa disimpan di browser.";
      });
    };
    serverDrafts.onchange = () => { get("restoreDraft").disabled = !serverDrafts.value; };
    get("restoreDraft").onclick = async () => {
      const key = serverDrafts.value;
      if (!key) return;
      try {
        const response = await fetch(backupUrl + "/" + encodeURIComponent(key), { headers: { Accept: "application/json" }, cache: "no-store" });
        if (!response.ok) throw new Error();
        const snapshot = await response.json();
        const record = { id: makeId(), status: "draft", updatedAt: new Date().toISOString(),
          backup: { key, session: backupSession, version: snapshot.version }, data: sanitizeData(snapshot.data) };
        records.unshift(record);
        saveRecords(records);
        loadRecordIntoWorkspace(record, true);
        renderDraftTable();
        status.textContent = "Salinan database dibuka di editor. Draf lokal sebelumnya tetap ada di daftar.";
      } catch {
        status.textContent = "Draf database gagal dibuka. Muat ulang untuk mencoba lagi.";
      }
    };
  }

  async function saveDraftExplicit() {
    if (databaseWorkspace) return saveSimulationDraft(false);
    const now = new Date().toISOString();
    data.organization = ORG_NAME;
    data.revision = normalizeRevision(data.revision);
    if (!activeRecordId) {
      activeRecordId = makeId();
      records.unshift({
        id: activeRecordId,
        status: "draft",
        parentId: null,
        parentRevision: null,
        childDraftId: null,
        updatedAt: now,
        submittedAt: null,
        approvalStage: null,
        revisionNote: null,
        data: structuredClone(data)
      });
    } else {
      const idx = records.findIndex((r) => r.id === activeRecordId);
      if (idx >= 0) {
        records[idx] = { ...records[idx], updatedAt: now, data: structuredClone(data) };
      }
    }
    const localSaved = saveRecords(records);
    updateActiveDocBadge();
    renderDraftTable();
    if (!backupUrl || !databaseBackup.checked) {
      notify(localSaved ? "Draf tersimpan di browser." : "Penyimpanan browser gagal.");
      return;
    }

    const record = findRecord(activeRecordId);
    const previous = record.backup?.session === backupSession ? record.backup : null;
    const key = previous?.key || record.id;
    if (pendingSaves.has(key)) {
      notify("Penyimpanan sedang berlangsung. Simpan kembali setelah selesai jika ada perubahan.");
      return;
    }
    const status = get("backupStatus");
    pendingSaves.add(key);
    status.textContent = "Menyimpan draf ke database…";
    try {
      const response = await fetch(backupUrl + "/" + encodeURIComponent(key), {
        method: "PUT",
        headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": root.dataset.token },
        body: JSON.stringify({ version: previous?.version || 0, data: record.data })
      });
      if (response.status === 409) {
        status.textContent = "Ada versi berbeda di database. Buka draf tersimpan untuk membandingkan dengan salinan lokal.";
        await refreshServerList(key);
        return;
      }
      if (!response.ok) throw new Error();
      const snapshot = await response.json();
      const current = findRecord(record.id);
      if (current) {
        current.backup = { key, session: backupSession, version: snapshot.version };
        saveRecords(records);
      }
      status.textContent = "Draf tersimpan di database sesi ini.";
      try { await refreshServerList(key); } catch { status.textContent += " Muat ulang untuk memperbarui daftar."; }
    } catch {
      status.textContent = "Penyimpanan ke database gagal. Periksa sesi dan koneksi, lalu coba lagi.";
    } finally {
      pendingSaves.delete(key);
    }
  }

  function submitCurrentSop() {
    if (!valid()) return;
    if (databaseWorkspace) return saveSimulationDraft(true);
    const now = new Date().toISOString();
    data.organization = ORG_NAME;
    data.revision = normalizeRevision(data.revision);

    if (activeRecordId) {
      const idx = records.findIndex((r) => r.id === activeRecordId);
      if (idx >= 0) {
        records[idx] = {
          ...records[idx],
          status: "in_review",
          updatedAt: now,
          submittedAt: now,
          approvalStage: reviewStages[0],
          data: structuredClone(data)
        };
      }
    } else {
      records.unshift({
        id: makeId(),
        status: "in_review",
        parentId: null,
        parentRevision: null,
        childDraftId: null,
        updatedAt: now,
        submittedAt: now,
        approvalStage: reviewStages[0],
        revisionNote: null,
        data: structuredClone(data)
      });
    }
    if (!saveRecords(records)) {
      records = loadRecords();
      notify("Pengajuan gagal disimpan. Periksa penyimpanan browser dan coba lagi.");
      return;
    }
    const submittedRevision = data.revision;

    const remaining = recordsByStatus("draft");
    if (remaining.length) loadRecordIntoWorkspace(remaining[0], false);
    else startFreshDraft(false);
    renderDraftTable();
    notify("SOP Revisi " + submittedRevision + " berhasil diajukan ke menu Dalam Pengajuan.");
  }

  // --- DALAM PENGAJUAN PAGE (TABLE + MODAL PDF PREVIEW) ---
  function renderInReviewTable() {
    const tbody = get("reviewListBody");
    const countBadge = get("reviewCountBadge");
    if (!tbody) return;

    const supervisor = pageMode === "supervisor";
    const statuses = supervisor ? ["in_review", "approved"] : ["in_review", "approved", "signed"];
    const list = records.filter(item => statuses.includes(item.status));
    if (countBadge) countBadge.textContent = list.length + " Dokumen";

    const filtered = list.filter((item) => {
      if (!searchQuery) return true;
      const hay = [item.data.title, item.data.number, item.data.unit, item.approvalStage, item.status === "in_review" ? reviewStages[item.decisions?.length || 0] : ""]
        .join(" ")
        .toLowerCase();
      return hay.includes(searchQuery);
    });

    tbody.replaceChildren();
    if (!filtered.length) {
      const tr = document.createElement("tr");
      const td = document.createElement("td");
      td.colSpan = 5;
      td.className = "text-center text-muted py-4 small";
      td.textContent = list.length
        ? "Tidak ada dokumen yang cocok dengan pencarian."
        : "Belum ada dokumen SOP yang sedang dalam pengajuan.";
      tr.append(td);
      tbody.append(tr);
      return;
    }

    for (const item of filtered) {
      const tr = document.createElement("tr");

      const tdDoc = document.createElement("td");
      tdDoc.className = "ps-3 py-3";
      const title = document.createElement("div");
      title.className = "fw-semibold text-dark";
      title.textContent = item.data.title || "—";
      const meta = document.createElement("div");
      meta.className = "small text-muted";
      meta.textContent =
        (item.data.number || "—") + " · Revisi " + normalizeRevision(item.data.revision);
      tdDoc.append(title, meta);

      const tdUnit = document.createElement("td");
      tdUnit.className = "py-3 small text-dark";
      tdUnit.textContent = item.data.unit || "—";

      const tdStage = document.createElement("td");
      tdStage.className = "py-3";
      const badge = document.createElement("span");
      badge.className = "badge text-bg-info-subtle text-info-emphasis border border-info-subtle";
      const decisions = item.decisions || [];
      const stage = reviewStages[decisions.length];
      badge.textContent = item.status === "in_review" ? stage : item.approvalStage;
      tdStage.append(badge);
      if (decisions.length) {
        const history = document.createElement("details");
        const summary = document.createElement("summary");
        summary.textContent = "Riwayat pemeriksaan";
        history.append(summary);
        for (const decision of decisions) {
          const line = document.createElement("div");
          line.textContent = decision.role + " \u00b7 " + formatTimestamp(decision.at);
          history.append(line);
        }
        tdStage.append(history);
      }

      const tdTime = document.createElement("td");
      tdTime.className = "py-3 small text-muted text-nowrap";
      tdTime.textContent = formatTimestamp(item.submittedAt || item.updatedAt);

      const tdActions = document.createElement("td");
      tdActions.className = "pe-3 py-3 text-end text-nowrap";
      const group = document.createElement("div");
      group.className = "d-inline-flex gap-1 flex-wrap justify-content-end";

      const previewBtn = document.createElement("button");
      previewBtn.type = "button";
      previewBtn.className = "btn btn-sm btn-primary";
      previewBtn.innerHTML = icon("eye") + '<span class="ms-1">Pratinjau PDF</span>';
      previewBtn.onclick = () => openPdfModalForData(item.data);

      const revBtn = document.createElement("button");
      revBtn.type = "button";
      revBtn.className = "btn btn-sm btn-outline-warning text-dark";
      revBtn.innerHTML = icon("alert") + '<span class="ms-1">Kembalikan Revisi</span>';
      revBtn.onclick = () => {
        pendingRevisionTargetId = item.id;
        get("revisionReviewerInput").value = stage;
        const noteInput = get("revisionNoteInput");
        if (noteInput) noteInput.value = "";
        get("requestRevisionDialog")?.showModal();
      };

      group.append(previewBtn);
      if (supervisor) {
        const approved = item.status === "approved";
        const action = approved ? "Simulasikan tanda tangan" : "Setujui";
        const decide = document.createElement("button");
        decide.type = "button";
        decide.className = "btn btn-sm btn-outline-primary";
        decide.textContent = action;
        decide.onclick = () => {
          const dialog = get("decisionDialog");
          get("decisionTitle").textContent = action;
          get("decisionSummary").textContent = [item.data.title, "Revisi " + item.data.revision,
            approved ? "Slot Direktur - Tanda tangan simulasi" : "Pemeriksa: " + stage].join(" \u00b7 ");
          const confirm = get("confirmDecision");
          confirm.textContent = action;
          confirm.onclick = () => {
            dialog.close();
            const at = new Date().toISOString();
            const next = reviewStages[decisions.length + 1];
            const updated = { ...item, updatedAt: at,
              decisions: [...decisions, { role: approved ? "Tanda tangan Direktur (simulasi)" : stage, at }],
              status: approved ? "signed" : next ? "in_review" : "approved",
              approvalStage: approved ? "Ditandatangani (simulasi)" : next || "Menunggu tanda tangan" };
            if (commitReview(updated)) renderInReviewTable();
          };
          dialog.showModal();
        };
        group.append(decide);
        if (!approved) group.append(revBtn);
      }
      tdActions.append(group);
      tr.append(tdDoc, tdUnit, tdStage, tdTime, tdActions);
      tbody.append(tr);
    }
  }

  // --- PERLU REVISI PAGE (TABLE + MODAL PREVIEW + ROUTE TO DRAF SOP AS REV + 1) ---
  function renderNeedsRevisionTable() {
    const tbody = get("revisionListBody");
    const countBadge = get("revisionCountBadge");
    if (!tbody) return;

    const list = recordsByStatus("needs_revision");
    if (countBadge) countBadge.textContent = list.length + " Dokumen";

    const filtered = list.filter((item) => {
      if (!searchQuery) return true;
      const hay = [
        item.data.title,
        item.data.number,
        item.data.unit,
        item.revisionNote?.note,
        item.revisionNote?.reviewer
      ]
        .join(" ")
        .toLowerCase();
      return hay.includes(searchQuery);
    });

    tbody.replaceChildren();
    if (!filtered.length) {
      const tr = document.createElement("tr");
      const td = document.createElement("td");
      td.colSpan = 5;
      td.className = "text-center text-muted py-4 small";
      td.textContent = list.length
        ? "Tidak ada dokumen perlu revisi yang cocok dengan pencarian."
        : "Tidak ada dokumen SOP yang perlu direvisi saat ini.";
      tr.append(td);
      tbody.append(tr);
      return;
    }

    for (const item of filtered) {
      const tr = document.createElement("tr");
      const currentRev = normalizeRevision(item.data.revision);
      const targetRev = nextRevisionNumber(currentRev);

      const tdDoc = document.createElement("td");
      tdDoc.className = "ps-3 py-3";
      const title = document.createElement("div");
      title.className = "fw-semibold text-dark";
      title.textContent = item.data.title || "—";
      const meta = document.createElement("div");
      meta.className = "small text-muted";
      meta.textContent = (item.data.number || "—") + " · Revisi " + currentRev;
      tdDoc.append(title, meta);

      const tdUnit = document.createElement("td");
      tdUnit.className = "py-3 small text-dark";
      tdUnit.textContent = item.data.unit || "—";

      const tdNote = document.createElement("td");
      tdNote.className = "py-3";
      tdNote.style.maxWidth = "340px";
      const noteBody = document.createElement("div");
      noteBody.className = "small text-dark";
      noteBody.textContent = item.revisionNote?.note || "—";
      const reviewerMeta = document.createElement("div");
      reviewerMeta.className = "text-muted mt-1";
      reviewerMeta.style.fontSize = "0.75rem";
      reviewerMeta.textContent = "Oleh: " + (item.revisionNote?.reviewer || "Pemeriksa");
      tdNote.append(noteBody, reviewerMeta);

      const tdTime = document.createElement("td");
      tdTime.className = "py-3 small text-muted text-nowrap";
      tdTime.textContent = formatTimestamp(item.revisionNote?.returnedAt || item.updatedAt);

      const tdActions = document.createElement("td");
      tdActions.className = "pe-3 py-3 text-end text-nowrap";
      const group = document.createElement("div");
      group.className = "d-inline-flex gap-1 flex-wrap justify-content-end";

      const previewBtn = document.createElement("button");
      previewBtn.type = "button";
      previewBtn.className = "btn btn-sm btn-outline-secondary";
      previewBtn.innerHTML = icon("eye") + '<span class="ms-1">Pratinjau PDF</span>';
      previewBtn.onclick = () => openPdfModalForData(item.data);

      const reviseBtn = document.createElement("button");
      reviseBtn.type = "button";
      reviseBtn.className = "btn btn-sm btn-warning fw-semibold";
      reviseBtn.innerHTML = icon("edit") + '<span class="ms-1">Revisi (Ke Rev ' + targetRev + ")</span>";
      reviseBtn.onclick = () => {
        const branched = createRevisionBranch(item);
        const url = new URL(drafSopUrl, window.location.origin);
        url.searchParams.set("edit", branched.id);
        window.location.href = url.toString();
      };

      group.append(previewBtn, reviseBtn);
      tdActions.append(group);
      tr.append(tdDoc, tdUnit, tdNote, tdTime, tdActions);
      tbody.append(tr);
    }
  }

  // --- EVENT BINDINGS ---
  root.querySelectorAll("[data-step]").forEach((b) => {
    b.onclick = () => switchStep(+b.dataset.step);
  });

  root.querySelectorAll("[data-draft-filter]").forEach((btn) => {
    btn.onclick = () => {
      draftListFilter = btn.dataset.draftFilter;
      root.querySelectorAll("[data-draft-filter]").forEach((el) => {
        el.classList.toggle("active", el.dataset.draftFilter === draftListFilter);
      });
      renderDraftTable();
    };
  });

  const prevStepBtn = get("prevStep");
  if (prevStepBtn) {
    prevStepBtn.onclick = () => {
      if (step > 0) switchStep(step - 1, true);
    };
  }

  const nextBtn = get("next");
  if (nextBtn) {
    nextBtn.onclick = () => {
      if (step === 0) switchStep(1, true);
      else saveDraftExplicit();
    };
  }

  const formEl = get("fields");
  if (formEl) formEl.onsubmit = (e) => e.preventDefault();

  root.querySelectorAll("[data-page]").forEach((b) => {
    b.onclick = () => {
      pageIndex += +b.dataset.page;
      displayPage();
      const tray = root.querySelector(".sop-tray");
      if (tray) tray.scrollTop = 0;
    };
  });

  const newDraftBtn = get("newDraftBtn");
  if (newDraftBtn) {
    newDraftBtn.onclick = () => {
      startFreshDraft(true);
      notify("Formulir disiapkan untuk SOP baru (Revisi 1).");
    };
  }

  const saveDraftBtn = get("saveDraftBtn");
  if (saveDraftBtn) saveDraftBtn.onclick = saveDraftExplicit;

  const submitSopBtn = get("submitSopBtn");
  if (submitSopBtn) submitSopBtn.onclick = submitCurrentSop;

  const searchInput = get("draftSearchInput");
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      searchQuery = searchInput.value.trim().toLowerCase();
      renderDraftTable();
    });
  }

  const reviewSearchInput = get("reviewSearchInput");
  if (reviewSearchInput) {
    reviewSearchInput.addEventListener("input", () => {
      searchQuery = reviewSearchInput.value.trim().toLowerCase();
      renderInReviewTable();
    });
  }

  const revisionSearchInput = get("revisionSearchInput");
  if (revisionSearchInput) {
    revisionSearchInput.addEventListener("input", () => {
      searchQuery = revisionSearchInput.value.trim().toLowerCase();
      renderNeedsRevisionTable();
    });
  }

  function commitReview(updated) {
    const next = records.map(record => record.id === updated.id ? updated : record);
    if (!saveRecords(next)) {
      notify("Keputusan gagal disimpan. Periksa penyimpanan browser dan muat ulang.");
      return false;
    }
    records = next;
    return true;
  }

  const confirmRevBtn = get("confirmRequestRevisionBtn");
  if (confirmRevBtn) {
    confirmRevBtn.onclick = () => {
      const noteVal = get("revisionNoteInput")?.value.trim();
      const reviewerVal = get("revisionReviewerInput")?.value.trim() || "Pemeriksa Mutu";
      if (!noteVal) {
        get("revisionNoteInput")?.classList.add("is-invalid");
        get("revisionNoteInput")?.focus();
        return;
      }
      get("revisionNoteInput")?.classList.remove("is-invalid");
      const idx = records.findIndex((r) => r.id === pendingRevisionTargetId);
      if (idx >= 0) {
        const now = new Date().toISOString();
        const updated = {
          ...records[idx],
          status: "needs_revision",
          updatedAt: now,
          approvalStage: "Dikembalikan oleh " + reviewerVal,
          revisionNote: {
            reviewer: reviewerVal,
            returnedAt: now,
            note: noteVal
          }
        };
        if (!commitReview(updated)) return;
      }
      get("requestRevisionDialog")?.close();
      renderInReviewTable();
      notify("Dokumen dikembalikan dan masuk ke daftar Perlu Revisi.");
    };
  }

  window.SopForm = Object.freeze({
    getDraft: () => structuredClone({ ...data, organization: ORG_NAME, revision: normalizeRevision(data.revision) }),
    async createPdf(customData = null) {
      const snapshot = customData
        ? structuredClone({ ...customData, organization: ORG_NAME, revision: normalizeRevision(customData.revision) })
        : this.getDraft();
      return generatePDF(createLayout(snapshot), snapshot);
    }
  });

  let previewUrl = null;
  let previewRequest = 0;

  async function openPdfModalForData(targetData = null) {
    const snapshot = targetData
      ? structuredClone({ ...targetData, organization: ORG_NAME, revision: normalizeRevision(targetData.revision) })
      : structuredClone({ ...data, organization: ORG_NAME, revision: normalizeRevision(data.revision) });
    modalPreviewData = snapshot;

    const request = ++previewRequest;
    const dialog = get("large");
    if (!dialog) return;
    const modalTitle = get("pdfModalTitle");
    if (modalTitle) {
      modalTitle.textContent = snapshot.title?.trim()
        ? "Pratinjau PDF · " + snapshot.title.trim() + " (Rev " + snapshot.revision + ")"
        : "Pratinjau Dokumen PDF";
    }
    dialog.showModal();
    get("pdfStatus").hidden = false;
    get("pdfStatus").textContent = "Menyiapkan dokumen PDF…";
    try {
      const blob = await window.SopForm.createPdf(snapshot);
      if (request !== previewRequest || !dialog.open) return;
      previewUrl = URL.createObjectURL(blob);
      const pageNum = targetData ? 1 : pageIndex + 1;
      const target = previewUrl + "#page=" + pageNum;
      get("pdfViewer").src = target;
      get("pdfTab").href = target;
      get("pdfTab").hidden = get("pdfViewer").hidden = false;
      get("pdfStatus").hidden = true;
    } catch {
      if (request === previewRequest) {
        get("pdfStatus").textContent = "PDF gagal dibuat. Tutup pratinjau dan coba kembali.";
      }
    }
  }

  const expandBtn = get("expand");
  if (expandBtn) expandBtn.onclick = () => openPdfModalForData(null);

  const largeDialog = get("large");
  if (largeDialog) {
    largeDialog.addEventListener("close", () => {
      ++previewRequest;
      modalPreviewData = null;
      get("pdfViewer").hidden = get("pdfTab").hidden = true;
      get("pdfViewer").removeAttribute("src");
      get("pdfTab").removeAttribute("href");
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = null;
    });
  }

  const guideBtn = get("guide");
  if (guideBtn) {
    guideBtn.onclick = () => get("helpDialog")?.showModal();
  }

  document.querySelectorAll(".sop-dialog-close").forEach((b) => {
    b.onclick = () => b.closest("dialog").close();
  });

  document.querySelectorAll(".sop-dialog").forEach((dialog) => {
    dialog.addEventListener("click", (e) => {
      if (e.target === dialog) {
        const r = dialog.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
          dialog.close();
        }
      }
    });
  });

  let busy = false;
  async function downloadPDF(customData = null) {
    if (busy) return;
    const usingModalData = customData && typeof customData.title === "string";
    if (!usingModalData && get("fields") && !valid()) return;
    busy = true;
    const exportBtn = get("export");
    if (exportBtn) exportBtn.disabled = true;
    try {
      const raw = usingModalData ? customData : data;
      const snapshot = structuredClone({
        ...raw,
        organization: ORG_NAME,
        revision: normalizeRevision(raw.revision)
      });
      const blob = await generatePDF(createLayout(snapshot), snapshot);
      const link = document.createElement("a");
      const url = URL.createObjectURL(blob);
      const base = [snapshot.number || "SOP", snapshot.title || "Dokumen", "Rev " + snapshot.revision]
        .join(" - ")
        .replace(/[<>:"/\\|?*\x00-\x1F]/g, "-")
        .slice(0, 170);
      link.download = base + ".pdf";
      link.href = url;
      document.body.append(link);
      link.click();
      link.remove();
      setTimeout(() => URL.revokeObjectURL(url), 60000);
      notify("Dokumen PDF siap diunduh.");
    } catch (err) {
      console.error(err);
      notify("Unduhan gagal. Silakan coba kembali.");
    } finally {
      busy = false;
      if (exportBtn) exportBtn.disabled = false;
    }
  }

  const exportBtn = get("export");
  if (exportBtn) exportBtn.onclick = () => downloadPDF(null);

  const modalDownloadPdfBtn = get("modalDownloadPdfBtn");
  if (modalDownloadPdfBtn) {
    modalDownloadPdfBtn.onclick = () => downloadPDF(modalPreviewData || data);
  }

  let savingSimulation = false;
  async function saveSimulationDraft(submit) {
    if (savingSimulation) return;
    savingSimulation = true;
    const snapshot = JSON.stringify(data);
    const status = get("save");
    status.textContent = "Menyimpan...";
    try {
      const response = await fetch(root.dataset.saveUrl, {
        method: "PUT", headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": root.dataset.token },
        body: JSON.stringify({ data, token: root.dataset.version, revision: root.dataset.revision, submit })
      });
      const result = await response.json();
      if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message);
      root.dataset.version = result.token;
      if (submit) window.location.reload();
      status.textContent = JSON.stringify(data) === snapshot ? "Tersimpan di database" : "Perubahan belum disimpan";
    } catch (error) {
      status.textContent = error.message || "Gagal menyimpan draf.";
    } finally {
      savingSimulation = false;
    }
  }

  // --- INITIALIZE BY PAGE MODE ---
  if (databaseWorkspace) {
    loadRecordIntoWorkspace({ id: root.dataset.revision, data: sanitizeData(window.simulationDraft) });
    return;
  }
  if (pageMode === "draft") {
    const params = new URLSearchParams(window.location.search);
    const editId = params.get("edit");
    const targetFromUrl = editId ? findRecord(editId) : null;
    const drafts = recordsByStatus("draft");

    if (targetFromUrl && targetFromUrl.status === "draft") {
      loadRecordIntoWorkspace(targetFromUrl, true);
    } else if (drafts.length) {
      loadRecordIntoWorkspace(drafts[0], false);
    } else {
      startFreshDraft(false);
    }
    renderDraftTable();
  } else if (pageMode === "in_review" || pageMode === "supervisor") {
    renderInReviewTable();
  } else if (pageMode === "needs_revision") {
    renderNeedsRevisionTable();
  }
})();
