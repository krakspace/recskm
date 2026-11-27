<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SopCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateSuperAdmin();
    }

    public function test_archive_and_current_library_show_the_corresponding_revisions(): void
    {
        $current = $this->get(route('pustaka.sop-berlaku'))->assertOk();
        $archive = $this->get(route('pustaka.arsip-sop'))->assertOk();

        foreach (config('sop_demo.documents') as $uuid => $document) {
            $url = route('verifikasi.publik', $uuid);
            if ($document['status'] === 'Berlaku') {
                $current->assertSee($url);
                $archive->assertDontSee($url);
            } else {
                $archive->assertSee($url);
                $current->assertDontSee($url);
            }
        }

        $this->get(route('pustaka.seluruh-sop-terbit', ['q' => 'SOP-ADM-001']))
            ->assertOk()->assertSeeText('Revisi 2')->assertDontSeeText('Peminjaman Ruang Rapat');
        $this->get(route('pustaka.sop-berlaku', ['q' => 'nomor-tidak-ada']))
            ->assertOk()->assertSeeText('Dokumen tidak ditemukan.');
    }

    public function test_verification_identifies_each_pdf_by_its_bytes_and_revision(): void
    {
        foreach (config('sop_demo.documents') as $uuid => $document) {
            $hash = hash_file('sha256', public_path('modul-sop-pdf/contoh/'.$uuid.'.pdf'));
            $this->get(route('verifikasi.publik', $uuid))->assertOk()
                ->assertSeeText($document['title'])->assertSeeText($document['status'])
                ->assertSee('data-hash="'.$hash.'"', false);
        }

        $this->get(route('verifikasi.publik', 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa'))->assertNotFound();
        $this->get(route('verifikasi.periksa-pdf', ['uuid' => 'unknown']))->assertOk()
            ->assertSeeText('UUID tidak ditemukan')->assertDontSee('id="verifyPdf"', false);
    }

    public function test_only_the_supervisor_workspace_offers_review_decisions(): void
    {
        $this->get(route('pengelolaan.dalam-pengajuan'))->assertOk()
            ->assertDontSee('id="decisionDialog"', false)->assertDontSee('id="requestRevisionDialog"', false);
        $this->get(route('pemeriksaan.antrean-supervisor'))->assertOk()
            ->assertSee('id="decisionDialog"', false)->assertSee('id="requestRevisionDialog"', false);
    }
}
