const input = document.getElementById('verifyPdf');
const result = document.getElementById('hashResult');

input.addEventListener('change', async () => {
    const file = input.files[0];
    if (!file) {
        result.textContent = 'Pilih salinan PDF untuk diperiksa.';
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        result.textContent = 'Ukuran berkas melebihi batas 20 MB.';
        return;
    }
    result.textContent = 'Memeriksa berkas...';
    try {
        const bytes = await file.arrayBuffer();
        const digest = await crypto.subtle.digest('SHA-256', bytes);
        if (file !== input.files[0]) return;
        const hash = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
        result.textContent = hash === input.dataset.hash
            ? 'Cocok. Berkas identik dengan PDF contoh revisi ini.'
            : 'Berbeda. Berkas tidak identik dengan PDF contoh revisi ini.';
    } catch {
        if (file === input.files[0]) result.textContent = 'Pemeriksaan gagal. Gunakan HTTPS atau localhost dan coba lagi.';
    }
});
