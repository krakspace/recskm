<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulationWorkflow
{
    public function decide(string $sop, string $revision, User $user, string $decision, string $note): bool
    {
        return DB::transaction(function () use ($sop, $revision, $user, $decision, $note) {
            if (! $user->is_active || ! $user->can('sop.review') || ! DB::table('sops')
                ->where('id', $sop)->where('current_revision_id', $revision)->exists()) {
                return false;
            }
            $pending = DB::table('approval_steps')->where('revision_id', $revision)->whereNull('decision');
            $step = (clone $pending)->orderBy('level')->first();
            if (! $step || $step->approver_user_id !== $user->id ||
                DB::table('sop_revisions')->where('id', $revision)->value('revision_status') !== 'IN_REVIEW') {
                return false;
            }
            $uuid = (string) Str::uuid();
            DB::table('approval_steps')->where('id', $step->id)->update([
                'decision' => $decision, 'note' => $note, 'decided_at' => now('UTC'), 'approval_uuid' => $uuid,
                'approver_snapshot' => json_encode(['name' => $user->name, 'role' => $step->role, 'unit' => $user->organization_code]),
            ]);
            $status = match (true) {
                $decision === 'tolak' => 'REVISION_REQUIRED',
                $pending->doesntExist() => 'APPROVED',
                default => 'IN_REVIEW',
            };
            DB::table('sop_revisions')->where('id', $revision)->update(['revision_status' => $status]);
            DB::table('sops')->where('id', $sop)->update(['status' => $status, 'updated_by' => $user->id, 'updated_at' => now()]);
            DB::table('audit_trails')->insert([
                'entity_type' => 'sop_revision', 'entity_id' => $revision, 'action' => $decision,
                'actor_id' => $user->id, 'metadata' => json_encode(['approval_uuid' => $uuid]), 'created_at' => now('UTC'),
            ]);

            return true;
        });
    }
}
