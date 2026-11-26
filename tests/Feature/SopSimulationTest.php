<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class SopSimulationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sop-test-'.Str::uuid();
        mkdir($this->directory);
        $this->app->useDatabasePath($this->directory);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $file = $this->directory.DIRECTORY_SEPARATOR.'sop-simulation.sqlite';
        if (is_file($file)) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function test_permissions_and_assignment_control_the_three_sequential_decisions(): void
    {
        $this->artisan('sop:simulate')->assertSuccessful();
        foreach (['admin', 'viewer', 'direktur'] as $username) {
            $this->artisan('sop:simulate', ['--as' => $username, '--decision' => 'setuju'])->assertFailed();
        }
        foreach (['kepala-unit', 'atasan', 'direktur'] as $username) {
            $this->artisan('sop:simulate', ['--as' => $username, '--decision' => 'setuju'])->assertSuccessful();
        }
        $this->artisan('sop:simulate', ['--as' => 'direktur', '--decision' => 'setuju'])->assertFailed();
        $db = new PDO('sqlite:'.database_path('sop-simulation.sqlite'));
        $this->assertSame('APPROVED', $db->query('select status from sops where sop_code = \'SIM-ADM-001\'')->fetchColumn());
        $this->assertEquals(4, $db->query("select count(*) from audit_trails where entity_id = (select current_revision_id from sops where sop_code = 'SIM-ADM-001')")->fetchColumn());
        $this->assertEquals(3, $db->query("select count(distinct approval_uuid) from approval_steps where revision_id = (select current_revision_id from sops where sop_code = 'SIM-ADM-001')")->fetchColumn());
    }

    public function test_rejection_requires_a_note_and_stops_the_current_revision(): void
    {
        $arguments = ['--as' => 'kepala-unit', '--decision' => 'tolak'];
        $this->artisan('sop:simulate', $arguments)->assertFailed();
        $this->artisan('sop:simulate', $arguments + ['--note' => 'Perjelas prosedur'])->assertSuccessful();
        $this->artisan('sop:simulate', ['--as' => 'atasan', '--decision' => 'setuju'])->assertFailed();
        $db = new PDO('sqlite:'.database_path('sop-simulation.sqlite'));
        $this->assertSame('REVISION_REQUIRED', $db->query("select revision_status from sop_revisions where id = (select current_revision_id from sops where sop_code = 'SIM-ADM-001')")->fetchColumn());
        $this->assertSame('Perjelas prosedur', $db->query("select note from approval_steps where level = 1 and revision_id = (select current_revision_id from sops where sop_code = 'SIM-ADM-001')")->fetchColumn());
    }

    public function test_reseeding_preserves_progress_and_the_published_pdf(): void
    {
        $this->artisan('sop:simulate', ['--as' => 'kepala-unit', '--decision' => 'setuju'])->assertSuccessful();
        $db = new PDO('sqlite:'.database_path('sop-simulation.sqlite'));
        $snapshot = static function () use ($db): array {
            $rows = [];
            foreach (['users', 'model_has_roles', 'sops', 'sop_revisions', 'approval_steps', 'signature_logs', 'audit_trails'] as $table) {
                $rows[$table] = $db->query('select * from '.$table.' order by rowid')->fetchAll(PDO::FETCH_ASSOC);
            }

            return $rows;
        };
        $before = $snapshot();
        $this->artisan('sop:simulate')->assertSuccessful();
        $this->assertSame($before, $snapshot());
        $this->assertCount(6, $before['users']);
        $this->assertEqualsCanonicalizing(['DRAFT', 'IN_REVIEW', 'REVISION_REQUIRED', 'PUBLISHED'], array_column($before['sops'], 'status'));
        $pdf = $db->query("select final_pdf, sha256_hash from sop_revisions where revision_status = 'PUBLISHED'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($pdf['sha256_hash'], hash('sha256', base64_decode($pdf['final_pdf'], true)));
        $this->assertEmpty($db->query('pragma foreign_key_check')->fetchAll());
    }
}
