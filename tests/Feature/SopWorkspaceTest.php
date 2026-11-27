<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SopWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateSuperAdmin();
    }

    public function test_draf_sop_places_editor_and_pdf_preview_above_draft_and_audit_history_table(): void
    {
        $response = $this->get(route('pengelolaan.draf-sop'));

        $response->assertOk()
            ->assertSee('data-page-mode="draft"', false)
            ->assertSeeHtmlInOrder([
                '<h1>Draf SOP</h1>',
                'id="newDraftBtn"',
                'id="saveDraftBtn"',
                'id="submitSopBtn"',
                'id="draftRevisionBanner"',
                'Editor Formulir SOP',
                'id="fields"',
                'Pratinjau PDF',
                'id="paper"',
                'Daftar Draf & Riwayat Revisi',
                'data-draft-filter="active"',
                'data-draft-filter="history"',
                'id="draftSearchInput"',
                'id="draftListBody"',
            ])
            ->assertSee(asset('modul-sop-pdf/blade-sop.css'), false)
            ->assertSee(asset('modul-sop-pdf/blade-sop.js'), false);
    }

    public function test_dalam_pengajuan_renders_table_only_with_pdf_preview_modal(): void
    {
        $response = $this->get(route('pengelolaan.dalam-pengajuan'));

        $response->assertOk()
            ->assertSee('data-page-mode="in_review"', false)
            ->assertDontSee('id="fields"', false)
            ->assertDontSee('id="paper"', false)
            ->assertSeeHtmlInOrder([
                '<h1>Dalam Pengajuan</h1>',
                'Daftar SOP Dalam Pengajuan',
                'id="reviewSearchInput"',
                'id="reviewListBody"',
                'id="large"',
                'id="modalDownloadPdfBtn"',
                'id="pdfViewer"',
            ]);
    }

    public function test_perlu_revisi_renders_table_only_and_routes_revision_to_draf_sop(): void
    {
        $response = $this->get(route('pengelolaan.perlu-revisi'));

        $response->assertOk()
            ->assertSee('data-page-mode="needs_revision"', false)
            ->assertSee('data-draf-sop-url="'.route('pengelolaan.draf-sop').'"', false)
            ->assertDontSee('id="fields"', false)
            ->assertDontSee('id="paper"', false)
            ->assertSeeHtmlInOrder([
                '<h1>Perlu Revisi</h1>',
                'Daftar SOP Perlu Revisi',
                'id="revisionSearchInput"',
                'id="revisionListBody"',
                'id="large"',
                'id="pdfViewer"',
            ]);
    }
}
