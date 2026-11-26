<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SimulationDatabase;
use App\Services\SimulationWorkflow;
use Database\Seeders\SopSimulationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateSop extends Command
{
    protected $signature = 'sop:simulate {--as= : Username simulasi} {--decision= : setuju atau tolak} {--note= : Catatan pemeriksa}';

    protected $description = 'Siapkan SQLite simulasi SOP dan coba keputusan pengguna contoh.';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Simulasi tersedia pada lingkungan local atau testing.');

            return self::FAILURE;
        }

        $file = database_path('sop-simulation.sqlite');
        if (! is_file($file) && ! touch($file)) {
            $this->error('Berkas SQLite simulasi gagal dibuat.');

            return self::FAILURE;
        }

        return SimulationDatabase::run(fn () => $this->simulate($file));
    }

    private function simulate(string $file): int
    {
        if ($this->callSilent('migrate', ['--database' => 'sop_simulation', '--path' => [
            'database/migrations', 'database/simulation/migrations',
        ]]) !== self::SUCCESS) {
            $this->error('Migrasi simulasi gagal.');

            return self::FAILURE;
        }
        (new SopSimulationSeeder)->run();

        $sop = DB::table('sops')->where('sop_code', 'SIM-ADM-001')->first();
        $this->info($file);
        $this->line($sop->sop_code.' | '.$sop->sop_title.' | '.$sop->status);
        $this->table(['Akun', 'Role', 'Izin memeriksa'], User::with('roles')->orderBy('username')->get()->map(fn (User $user) => [
            $user->username, $user->getRoleNames()->implode(', '), $user->can('sop.review') ? 'Ya' : 'Tidak',
        ]));

        if ($this->option('decision') !== null) {
            $decision = $this->option('decision');
            $note = trim((string) $this->option('note'));
            $user = User::where('username', $this->option('as'))->first();
            if (! in_array($decision, ['setuju', 'tolak'], true) || ($decision === 'tolak' && $note === '')) {
                $this->error('Gunakan setuju atau tolak. Penolakan memerlukan --note.');

                return self::FAILURE;
            }
            if (! $user?->is_active || ! $user->can('sop.review')) {
                $this->error('Akun memerlukan izin sop.review.');

                return self::FAILURE;
            }
            if (! app(SimulationWorkflow::class)->decide($sop->id, $sop->current_revision_id, $user, $decision, $note)) {
                $this->error('Akun tidak ditugaskan pada tahap aktif atau revisi sudah selesai diperiksa.');

                return self::FAILURE;
            }
        }

        $this->line('Status revisi: '.DB::table('sop_revisions')->where('id', $sop->current_revision_id)->value('revision_status'));
        $steps = DB::table('approval_steps')->where('revision_id', $sop->current_revision_id)->orderBy('level')->get();
        $this->table(['Tahap', 'Pemeriksa', 'Keputusan'], $steps->map(fn ($step) => [$step->level, $step->role, $step->decision ?? 'Belum diputuskan']));

        return self::SUCCESS;
    }
}
