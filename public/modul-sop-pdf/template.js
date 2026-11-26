const FIXED_ORGANIZATION = "PT Krakatau Medika";

const example = {
  title: "Permohonan Pengembangan Sistem Aplikasi",
  number: "Pro_KM.75.003",
  revision: "1",
  unit: "Information, Communication & Technology",
  organization: FIXED_ORGANIZATION,
  definition: "Tata cara permohonan pengembangan sistem aplikasi seperti tambahan menu / format data dalam aplikasi.",
  purpose: "1. Mendokumentasikan segala bentuk pengembangan terhadap sistem aplikasi.\n2. Evaluasi pengembangan sistem aplikasi apakah sudah sesuai dengan kebijakan yang berlaku di PT Krakatau Medika dan juga kebutuhan user unit pemohon.",
  policy: "SK Direktur No. 94/Dir-KM/Kpts/X/2022 tentang Pemberlakuan Pedoman Pelayanan Information & Communication Technology PT Krakatau Medika.",
  procedure: "Unit pemohon:\n1. Minta Form Permohonan Pengembangan Sistem Aplikasi.\n2. Jelaskan pengembangan sistem aplikasi yang diminta.\n3. Serahkan formulir ke Assistant Manager Information & Communication Technology.\n\nAssistant Manager Information & Communication Technology:\n1. Terima Form Permohonan Pengembangan Sistem Aplikasi.\n2. Verifikasi ke unit pemohon mengenai pengembangan yang diminta.\n3. Jelaskan semua keterkaitan sistem aplikasi dan konsekuensi yang harus dilakukan atas pengembangan sistem aplikasi tersebut.\n4. Minta unit pemohon untuk membuat Memo Dinas yang ditujukan kepada Manager Information & Communication Technology jika unit pemohon menyetujui konsekuensi yang telah dijelaskan.\n5. Minta evaluasi dari Manager Information & Communication Technology apakah pengembangan sistem yang diusulkan dapat dilakukan atau tidak.\n6. Informasikan ke unit peminta jika usulan tidak disetujui.\n7. Lakukan coding data program sesuai dengan Memo Dinas.\n8. Informasikan ke unit pemohon sistem aplikasi yang sudah dikembangkan.\n9. Pinta unit pemohon untuk mencoba sistem aplikasi yang sudah dikembangkan.",
  related: "1. Seluruh unit layanan di lingkungan PT Krakatau Medika."
};

const sections = [
  {
    title: "Identitas dokumen",
    fields: [
      ["title", "Judul SOP", "textarea", true, 180],
      ["number", "Nomor dokumen", "text", true, 55],
      ["unit", "Unit penyusun", "text", true, 110]
    ]
  },
  {
    title: "Isi standar prosedur",
    fields: [
      ["definition", "Pengertian", "textarea", true],
      ["purpose", "Tujuan", "textarea", true],
      ["policy", "Kebijakan", "textarea", true],
      ["procedure", "Prosedur", "quill", true],
      ["related", "Unit terkait", "textarea", true]
    ]
  }
];
