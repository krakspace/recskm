<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class SopScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== database_path('sop-simulation.sqlite')) {
            throw new LogicException('Gunakan php artisan sop:simulate.');
        }

        $directory = __DIR__.'/fixtures';
        $scenarios = json_decode(file_get_contents($directory.'/sop-scenarios.json'), true, flags: JSON_THROW_ON_ERROR);
        $accounts = DB::table('users')->pluck('id', 'username')->all();
        $category = DB::table('sop_categories')->where('code', 'ADM')->firstOrFail()->id;
        $template = DB::table('sop_templates')->where('name', 'SOP Administrasi')->where('version', 1)->firstOrFail()->id;
        $slot = DB::table('signature_slots')->where('template_id', $template)->where('role', 'direktur')->firstOrFail()->id;

        DB::transaction(function () use ($scenarios, $accounts, $category, $template, $slot, $directory) {
            foreach ($scenarios as $scenario) {
                $document = $this->resolveUsers($scenario['sop'], $accounts);
                if (DB::table('sops')->where('id', $document['id'])->exists()) {
                    continue;
                }
                $current = $document['current_revision_id'];
                $document['current_revision_id'] = null;
                DB::table('sops')->insert($document + ['category_id' => $category]);

                foreach ($scenario['revisions'] as $revision) {
                    $revision = $this->resolveUsers($revision, $accounts);
                    $revision['content'] = json_encode($revision['content'], JSON_THROW_ON_ERROR);
                    if ($revision['revision_status'] === 'PUBLISHED') {
                        $pdf = file_get_contents($directory.'/distribusi-surat.pdf');
                        if (! hash_equals($revision['sha256_hash'], hash('sha256', $pdf))) {
                            throw new LogicException('Checksum PDF contoh tidak cocok.');
                        }
                        $revision['final_pdf'] = base64_encode($pdf);
                    }
                    DB::table('sop_revisions')->insert($revision + ['template_id' => $template]);
                }

                DB::table('sops')->where('id', $document['id'])->update(['current_revision_id' => $current]);
                foreach (['approval_steps', 'signature_logs', 'audit_trails'] as $table) {
                    foreach ($scenario[$table] as $row) {
                        $row = $this->resolveUsers($row, $accounts);
                        DB::table($table)->insert($table === 'signature_logs' ? $row + ['slot_id' => $slot] : $row);
                    }
                }
            }
        });
    }

    private function resolveUsers(array $row, array $accounts): array
    {
        foreach (['created_by', 'updated_by', 'submitted_by', 'approver_user_id', 'signer_user_id', 'actor_id'] as $column) {
            if (isset($row[$column])) {
                $row[$column] = $accounts[$row[$column]];
            }
        }

        return $row;
    }
}
