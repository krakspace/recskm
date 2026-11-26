<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class SopSimulationSeeder extends Seeder
{
    public const PASSWORD = 'Latihan-SOP-2026!';

    public function run(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== database_path('sop-simulation.sqlite')) {
            throw new LogicException('Jalankan php artisan sop:simulate untuk mengisi database simulasi.');
        }

        DB::transaction(function () {
            $permissions = [
                'viewer' => ['sop.view'],
                'drafter' => ['sop.view', 'sop.draft', 'sop.submit'],
                'supervisor' => ['sop.view', 'sop.review', 'sop.sign'],
                'administrator' => ['sop.view', 'sop.configure', 'sop.archive', 'sop.revoke'],
            ];
            foreach ($permissions as $roleName => $names) {
                $role = Role::findOrCreate($roleName, 'web');
                foreach ($names as $name) {
                    $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
                }
            }

            DB::table('organizations')->insertOrIgnore(['id' => 'ADM', 'code' => 'ADM', 'name' => 'Administrasi']);
            $accounts = [
                'viewer' => ['viewer', 'Pembaca', 'Mira Dewi'],
                'drafter' => ['drafter', 'Penyusun', 'Nadia Putri'],
                'kepala-unit' => ['supervisor', 'Kepala Unit', 'Bima Santoso'],
                'atasan' => ['supervisor', 'Atasan', 'Ratih Wulandari'],
                'direktur' => ['supervisor', 'Direktur', 'Arif Pratama'],
                'admin' => ['administrator', 'Administrator', 'Dian Sari'],
            ];
            $users = [];
            foreach ($accounts as $username => [$role, $position, $name]) {
                $user = User::where('username', $username)->first();
                if (! $user) {
                    $id = (string) Str::uuid();
                    DB::table('users')->insert([
                        'id' => $id, 'username' => $username, 'name' => $position.' Simulasi',
                        'email' => $username.'@example.invalid', 'organization_id' => 'ADM',
                        'organization_code' => 'ADM', 'employee_code' => 'SIM-'.$username,
                        'password' => '!', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $user = User::findOrFail($id);
                }
                if ($user->getRawOriginal('password') === '!') {
                    $user->forceFill(['name' => $name, 'password' => self::PASSWORD])->save();
                }
                $user->assignRole($role);
                DB::table('positions')->insertOrIgnore(['name' => $position]);
                DB::table('employees')->insertOrIgnore([
                    'employee_code' => 'SIM-'.$username, 'user_id' => $user->id, 'organization_id' => 'ADM',
                    'position_id' => DB::table('positions')->where('name', $position)->value('id'),
                ]);
                $users[$username] = $user->id;
            }

            $templateId = DB::table('sop_templates')->where('name', 'SOP Administrasi')->where('version', 1)->value('id');
            if ($templateId) {
                DB::table('signature_slots')->where('template_id', $templateId)->where('role', 'direktur')
                    ->where('allowed_modes', '["qr"]')->update(['allowed_modes' => '["simulation","qr"]']);
            }

            if (! DB::table('sops')->where('sop_code', 'SIM-ADM-001')->exists()) {
                $this->seedFirstReview($users);
            }

            (new SopScenarioSeeder)->run();
        });
    }

    private function seedFirstReview(array $users): void
    {
        $category = DB::table('sop_categories')->insertGetId(['code' => 'ADM', 'name' => 'Administrasi']);
        $template = DB::table('sop_templates')->insertGetId([
            'name' => 'SOP Administrasi', 'version' => 1, 'layout' => json_encode(['paper' => 'Letter']),
        ]);
        DB::table('signature_slots')->insert(['template_id' => $template, 'role' => 'direktur', 'allowed_modes' => '["simulation","qr"]']);
        $sop = (string) Str::uuid();
        $revision = (string) Str::uuid();
        DB::table('sops')->insert([
            'id' => $sop, 'sop_code' => 'SIM-ADM-001', 'sop_title' => 'Serah Terima Dokumen Unit',
            'organization_id' => 'ADM', 'category_id' => $category, 'visibility' => 'private',
            'status' => 'IN_REVIEW', 'created_by' => $users['drafter'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('sop_revisions')->insert([
            'id' => $revision, 'sop_id' => $sop, 'template_id' => $template, 'revision_number' => 1,
            'version' => '1', 'revision_status' => 'IN_REVIEW', 'submitted_by' => $users['drafter'],
            'submitted_at' => now(), 'locked_at' => now(),
            'content' => json_encode(['ops' => [['insert' => "Catat penerimaan dokumen unit.\n"]]]),
        ]);
        DB::table('sops')->where('id', $sop)->update(['current_revision_id' => $revision]);
        foreach (['kepala-unit', 'atasan', 'direktur'] as $index => $username) {
            DB::table('approval_steps')->insert([
                'id' => (string) Str::uuid(), 'revision_id' => $revision, 'level' => $index + 1,
                'role' => $username === 'kepala-unit' ? 'head_unit' : $username, 'approver_user_id' => $users[$username],
            ]);
        }
        DB::table('audit_trails')->insert([
            'entity_type' => 'sop_revision', 'entity_id' => $revision, 'action' => 'submitted',
            'actor_id' => $users['drafter'], 'metadata' => '{}', 'created_at' => now(),
        ]);
    }
}
