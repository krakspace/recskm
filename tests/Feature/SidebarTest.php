<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarTest extends TestCase
{
    public function test_dashboard_shows_the_full_sidebar_without_signing_in(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSeeText('Tugas Saya')
            ->assertDontSeeText('Buat SOP')
            ->assertDontSeeText('Draf Unit')
            ->assertSeeTextInOrder([
                'Beranda',
                'Pengelolaan SOP',
                'Draf SOP',
                'Dalam Pengajuan',
                'Perlu Revisi',
                'Pustaka SOP',
                'SOP Berlaku',
                'Seluruh SOP Terbit',
                'Arsip SOP',
                'Verifikasi Dokumen',
                'Cek Status SOP',
                'Periksa PDF',
                'Administrasi',
                'Template SOP',
                'Peran, Unit & Hak Akses',
                'Rute Persetujuan',
                'Aturan Penomoran',
                'Audit',
                'Log Aktivitas',
            ])
            ->assertSeeHtmlInOrder([
                'class="sidebar-link sidebar-home"',
                'href="'.route('dashboard').'"',
                'aria-current="page"',
                'Beranda',
            ]);
    }

    public function test_dashboard_has_a_visibility_toggle_inside_the_sidebar(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertSeeHtmlInOrder([
            'id="app-sidebar"',
            'id="sidebar-toggle"',
            'aria-controls="sidebar-nav"',
            'aria-expanded="true"',
            'id="sidebar-nav"',
            '</aside>',
        ])->assertDontSee('id="sidebar-position"', false);
    }

    public function test_draf_sop_marks_the_draft_link_as_current(): void
    {
        $response = $this->get(route('pengelolaan.draf-sop'));

        $response->assertOk()
            ->assertSeeHtmlInOrder([
                'Pengelolaan SOP',
                'href="'.route('pengelolaan.draf-sop').'"',
                'aria-current="page"',
                'Draf SOP',
                'href="'.route('pengelolaan.dalam-pengajuan').'"',
                'Dalam Pengajuan',
                'href="'.route('pengelolaan.perlu-revisi').'"',
                'Perlu Revisi',
            ]);
    }

    public function test_draf_sop_breadcrumb_links_back_to_the_dashboard(): void
    {
        $response = $this->get(route('pengelolaan.draf-sop'));

        $response->assertOk()->assertSeeHtmlInOrder([
            'aria-label="Breadcrumb"',
            '<a href="'.route('dashboard').'">Beranda</a>',
            '<span>Pengelolaan SOP</span>',
            '<span aria-current="page">Draf SOP</span>',
        ]);
    }

    public function test_each_submenu_opens_its_page_with_the_matching_breadcrumb(): void
    {
        $dashboard = $this->get(route('dashboard'));

        foreach (config('sidebar') as $section => $items) {
            foreach ($items as $label => $name) {
                $dashboard->assertSee('href="'.route($name).'"', false);
                $this->get(route($name))->assertOk()->assertSeeHtmlInOrder([
                    'href="'.route($name).'"',
                    'aria-current="page"',
                    'aria-label="Breadcrumb"',
                    '<span>'.e($section).'</span>',
                    '<span aria-current="page">'.e($label).'</span>',
                    '<h1>'.e($label).'</h1>',
                ]);
            }
        }
    }
}
