# Modul form SOP dan PDF

Modul ini menyediakan formulir berbahasa Indonesia dengan dua bagian, **Identitas** dan **Isi SOP**. Pengguna dapat mengedit prosedur dengan Quill, melihat pergantian halaman saat mengetik, membuka berkas PDF di penampil browser, lalu mengunduhnya. Semua pustaka disertakan dalam folder modul.

## Pasang di Laravel

1. Salin folder `modul-sop-pdf` utuh ke direktori `public/` di repositori Laravel. Pastikan hasilnya `public/modul-sop-pdf/index.html`.
2. Jalankan aplikasi Laravel seperti biasa. Untuk pengembangan lokal, jalankan `php artisan serve` dari akar repositori.
3. Buka `/modul-sop-pdf/index.html` pada alamat aplikasi. Untuk menambahkannya ke menu Blade, gunakan tautan berikut.

```blade
<a href="{{ asset('modul-sop-pdf/index.html') }}">Penyusun SOP</a>
```

Untuk menampilkan form di dalam layout Blade yang sudah ada, gunakan iframe berikut. Editor dan pratinjau menggunakan stylesheet sendiri sehingga tampilan layout induk tetap terpisah.

```blade
<iframe
    id="sop-module"
    title="Penyusun SOP"
    src="{{ asset('modul-sop-pdf/index.html') }}"
    style="display:block; width:100%; height:85vh; border:0"
></iframe>
```

Pemasangan di `public/` menyajikan halaman sebagai aset statis. Middleware pada route Blade induk hanya berlaku untuk route tersebut. Form bekerja langsung di browser tanpa migrasi, Composer, npm, atau Vite. Untuk mencoba modul sebelum digabungkan, buka `index.html` langsung di browser.

## Cara kerja

1. **Identitas.** Judul, nomor dokumen, revisi, unit penyusun, dan instansi membentuk kop yang diulang pada setiap halaman. Data awal berasal dari contoh SOP permohonan pengembangan sistem aplikasi.
2. **Isi SOP.** Pengertian, tujuan, kebijakan, dan unit terkait menggunakan teks biasa. Editor Quill pada kolom Prosedur menyediakan tebal, miring, garis bawah, daftar bernomor, dan daftar berpoin. Tekan Enter untuk membuat langkah berikutnya.
3. **Draf.** Setiap perubahan disimpan pada `localStorage` browser. Kuncinya berawalan `sysman.sop.v2:` dan menyertakan jalur folder modul. Satu lokasi modul menyimpan satu draf per profil browser. Jika penyimpanan lokal dinonaktifkan, pengguna tetap dapat mengunduh PDF selama halaman terbuka. Pada origin yang sama, modul dapat memuat draf versi sebelumnya dengan Prosedur berupa teks biasa.
4. **Pratinjau.** Modul mengukur teks, menyusun tabel pada kertas Letter potret, lalu membagi baris panjang ke halaman berikutnya. Panel di samping form menampilkan susunan halaman ini secara langsung. Tombol perbesar membuat berkas PDF dari data saat itu dan membukanya di penampil PDF browser.
5. **Unduhan.** Tombol **Unduh PDF** memeriksa kolom wajib, mengambil salinan draf, lalu membuat PDF melalui pdf-lib. Kop, penomoran halaman, baris tabel, dan format Quill mengikuti tata letak yang sama dengan panel pratinjau. Nama berkas memuat nomor, judul, dan revisi.

## Hubungkan dengan logika aplikasi

Gunakan API berikut dari halaman Blade jika iframe dan halaman induk berada pada **origin yang sama**. Panggil setelah iframe selesai dimuat, misalnya dari handler tombol milik aplikasi. Modul sendiri menyimpan draf di browser. Aplikasi induk menentukan kapan draf atau berkas dikirim ke Laravel.

```js
const moduleWindow = document.getElementById('sop-module').contentWindow;
const draft = moduleWindow.SopForm.getDraft();         // Salinan data form
const pdf = await moduleWindow.SopForm.createPdf();    // Blob application/pdf
```

`createPdf()` juga dapat menghasilkan pratinjau draf yang belum lengkap. Tombol unduh pada form mewajibkan seluruh kolom bertanda bintang. Jika aplikasi induk menyimpan dokumen ke server, validasi data pada endpoint Laravel milik aplikasi.

Draf berisi `title`, `number`, `revision`, `unit`, `organization`, `definition`, `purpose`, `policy`, `procedure`, dan `related`. Semua nilai berupa string kecuali `procedure`, yang menggunakan Quill Delta. Pertahankan objek Delta saat menyimpan draf sebagai JSON agar penekanan teks dan daftar tetap tersedia. [Dokumentasi Quill Delta](https://quilljs.com/docs/delta)

Contoh nilai `procedure` untuk satu langkah bernomor dengan kata pertama dicetak tebal.

```json
{
  "ops": [
    { "insert": "Periksa", "attributes": { "bold": true } },
    { "insert": " kelengkapan formulir." },
    { "insert": "\n", "attributes": { "list": "ordered" } }
  ]
}
```

## Berkas yang biasa disesuaikan

| Berkas | Fungsi |
| --- | --- |
| `index.html`, `style.css` | Struktur dan tampilan halaman. |
| `template.js` | Nilai contoh, label, urutan, dan kewajiban kolom. |
| `app.js` | Form, Quill, validasi, penyimpanan draf, dan API untuk iframe. |
| `procedure.js` | Pembacaan Delta, penomoran, dan pembungkusan teks berformat. |
| `layout.js` | Ukuran kertas, lebar kolom, kop, pembagian halaman, dan pratinjau langsung. |
| `export.js` | Pembuatan Blob PDF dari tata letak dokumen. |
| `assets/` | Quill 2.0.3 dengan tema Snow, pdf-lib, lisensi, dan logo contoh. |

Untuk mengganti identitas visual, ubah kondisi nama instansi pada `layout.js` dan data gambar di `assets/logo-data.js`. Instansi dengan nama lain secara bawaan ditampilkan sebagai teks dalam kop.

## Cek setelah pemasangan

- Ubah judul dan pastikan kop pada pratinjau ikut berubah.
- Di Prosedur, buat dua langkah bernomor dan tebalkan salah satu kata. Muat ulang halaman dan periksa apakah daftar dan penekanannya tersimpan.
- Buka pratinjau diperbesar, lalu unduh PDF. Periksa nomor dokumen, urutan langkah, dan isi halaman terakhir.

