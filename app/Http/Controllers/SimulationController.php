<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SimulationWorkflow;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SimulationController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('simulation')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function user(): User
    {
        return Auth::guard('simulation')->user();
    }

    private function visible(): Builder
    {
        $user = $this->user();
        abort_unless($user->can('sop.view'), 403);

        return DB::table('sops')->where('organization_id', $user->organization_id)
            ->where(function (Builder $query) use ($user) {
                $query->where('created_by', $user->id)->orWhere('status', 'PUBLISHED')
                    ->orWhereIn('current_revision_id', DB::table('approval_steps')
                        ->select('revision_id')->where('approver_user_id', $user->id));
            });
    }

    public function index(): View
    {
        return view('pengelolaan.simulasi-index', ['documents' => $this->visible()->orderByDesc('updated_at')->get(), 'user' => $this->user()]);
    }

    public function create(): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user->can('sop.draft'), 403);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($id, $user) {
            $revision = (string) Str::uuid();
            DB::table('sops')->insert(['id' => $id, 'sop_code' => 'SIM-'.Str::upper(Str::random(10)),
                'sop_title' => 'Draf baru', 'created_by' => $user->id, 'organization_id' => $user->organization_id,
                'category_id' => DB::table('sop_categories')->where('code', 'ADM')->firstOrFail()->id,
                'status' => 'DRAFT', 'visibility' => 'private', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('sop_revisions')->insert(['id' => $revision, 'sop_id' => $id,
                'template_id' => DB::table('sop_templates')->where('is_active', true)->firstOrFail()->id,
                'revision_number' => 1, 'version' => '1', 'revision_status' => 'DRAFT', 'content' => '{}']);
            DB::table('sops')->where('id', $id)->update(['current_revision_id' => $revision]);
            $this->audit($revision, 'created');
        });

        return redirect()->route('sim.show', $id);
    }

    public function show(string $sop): View
    {
        $document = $this->visible()->where('id', $sop)->firstOrFail();
        $revision = DB::table('sop_revisions')->select('id', 'revision_number', 'content', 'change_notes')
            ->where('id', $document->current_revision_id)->firstOrFail();
        $content = json_decode($revision->content, true, flags: JSON_THROW_ON_ERROR);
        $data = (isset($content['ops']) ? ['procedure' => $content] : $content) + [
            'title' => $document->sop_title, 'number' => $document->sop_code, 'unit' => 'Administrasi',
            'revision' => (string) $revision->revision_number, 'definition' => '', 'purpose' => '', 'policy' => '',
            'related' => '', 'procedure' => ['ops' => [['insert' => "\n"]]],
        ];
        $data['revision'] = (string) $revision->revision_number;
        $history = DB::table('approval_steps as steps')->join('sop_revisions as rev', 'rev.id', '=', 'steps.revision_id')
            ->join('users', 'users.id', '=', 'steps.approver_user_id')->where('rev.sop_id', $sop)
            ->orderByDesc('rev.revision_number')->orderBy('level')->get(['steps.*', 'users.name', 'rev.revision_number']);
        $current = $history->where('revision_id', $revision->id);
        $user = $this->user();
        $editable = $document->status === 'DRAFT' && $document->created_by === $user->id && $user->can('sop.draft');
        $canReview = $document->status === 'IN_REVIEW' && $user->can('sop.review')
            && $current->firstWhere('decision', null)?->approver_user_id === $user->id;
        $canFinalize = $document->status === 'APPROVED' && $user->can('sop.sign')
            && $current->firstWhere('role', 'direktur')?->approver_user_id === $user->id;

        return view('pengelolaan.simulasi-detail', compact('document', 'revision', 'data', 'user', 'editable', 'canReview', 'canFinalize', 'history'));
    }

    public function save(Request $request, string $sop): JsonResponse
    {
        $user = $this->user();
        abort_unless($user->can('sop.draft'), 403);
        $submit = $request->boolean('submit');
        abort_if($submit && ! $user->can('sop.submit'), 403);
        $presence = $submit ? 'required' : 'nullable';
        $input = $request->validate([
            'revision' => 'required|uuid', 'token' => 'required|string|size:64', 'submit' => 'required|boolean',
            'data' => 'required|array:title,number,unit,revision,organization,definition,purpose,policy,procedure,related',
            'data.title' => 'required|string|max:180', 'data.number' => ['required', 'string', 'max:55', Rule::unique('sops', 'sop_code')->ignore($sop)],
            'data.unit' => [$presence, 'string', 'max:110'], 'data.definition' => [$presence, 'string', 'max:30000'],
            'data.purpose' => [$presence, 'string', 'max:30000'], 'data.policy' => [$presence, 'string', 'max:30000'],
            'data.related' => [$presence, 'string', 'max:30000'], 'data.procedure' => 'required|array:ops',
            'data.procedure.ops' => 'required|list|max:5000', 'data.procedure.ops.*' => 'required|array:insert,attributes',
            'data.procedure.ops.*.insert' => 'present|string|max:30000',
            'data.procedure.ops.*.attributes' => 'sometimes|array:bold,italic,underline,list',
            'data.procedure.ops.*.attributes.list' => 'sometimes|in:ordered,bullet',
            'data.procedure.ops.*.attributes.bold' => 'sometimes|boolean', 'data.procedure.ops.*.attributes.italic' => 'sometimes|boolean',
            'data.procedure.ops.*.attributes.underline' => 'sometimes|boolean',
        ]);
        $data = $input['data'];
        if ($submit && trim(implode('', array_column($data['procedure']['ops'], 'insert'))) === '') {
            throw ValidationException::withMessages(['data.procedure' => 'Prosedur wajib diisi.']);
        }
        $token = DB::transaction(function () use ($sop, $input, $data, $submit, $user) {
            $document = $this->visible()->where('id', $sop)->firstOrFail();
            abort_unless($document->created_by === $user->id, 403);
            abort_unless($document->status === 'DRAFT' && $document->current_revision_id === $input['revision'], 409);
            $revision = DB::table('sop_revisions')->where('id', $input['revision'])->firstOrFail();
            abort_unless(hash_equals(hash('sha256', $revision->content), $input['token']), 409, 'Draf telah berubah. Muat ulang untuk membandingkan.');
            $data['revision'] = (string) $revision->revision_number;
            $content = json_encode($data, JSON_THROW_ON_ERROR);
            $status = $submit ? 'IN_REVIEW' : 'DRAFT';
            DB::table('sop_revisions')->where('id', $revision->id)->update(['content' => $content, 'revision_status' => $status]
                + ($submit ? ['submitted_by' => $user->id, 'submitted_at' => now(), 'locked_at' => now()] : []));
            DB::table('sops')->where('id', $sop)->update(['sop_title' => $data['title'], 'sop_code' => $data['number'],
                'status' => $status, 'updated_at' => now(), 'updated_by' => $user->id]);
            if ($submit) {
                foreach (['kepala-unit', 'atasan', 'direktur'] as $index => $username) {
                    $reviewer = User::where('username', $username)->where('is_active', true)->where('organization_id', $user->organization_id)->firstOrFail();
                    DB::table('approval_steps')->insert(['id' => (string) Str::uuid(), 'level' => $index + 1,
                        'role' => $index === 0 ? 'head_unit' : $username, 'approver_user_id' => $reviewer->id, 'revision_id' => $revision->id]);
                }
                $this->audit($revision->id, 'submitted');
            }

            return hash('sha256', $content);
        });

        return response()->json(['token' => $token]);
    }

    public function revise(Request $request, string $sop): RedirectResponse
    {
        $revision = $request->validate(['revision' => 'required|uuid'])['revision'];
        DB::transaction(function () use ($sop, $revision) {
            $document = $this->visible()->where('id', $sop)->firstOrFail();
            abort_unless($document->created_by === $this->user()->id && $this->user()->can('sop.draft'), 403);
            abort_unless($document->status === 'REVISION_REQUIRED' && $document->current_revision_id === $revision, 409);
            $old = DB::table('sop_revisions')->where('id', $revision)->firstOrFail();
            $id = (string) Str::uuid();
            DB::table('sop_revisions')->insert(['id' => $id, 'sop_id' => $sop, 'template_id' => $old->template_id,
                'revision_number' => $old->revision_number + 1, 'version' => (string) ($old->revision_number + 1),
                'content' => $old->content, 'revision_status' => 'DRAFT', 'change_notes' => DB::table('approval_steps')
                    ->where('revision_id', $revision)->where('decision', 'tolak')->value('note')]);
            DB::table('sops')->where('id', $sop)->update(['current_revision_id' => $id, 'status' => 'DRAFT', 'updated_at' => now()]);
            $this->audit($id, 'revised');
        });

        return redirect()->route('sim.show', $sop);
    }

    public function review(Request $request, string $sop, SimulationWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['revision' => 'required|uuid', 'decision' => 'required|in:setuju,tolak', 'note' => 'required_if:decision,tolak|nullable|string|max:3000']);
        $this->visible()->where('id', $sop)->firstOrFail();
        abort_unless($workflow->decide($sop, $data['revision'], $this->user(), $data['decision'], trim($data['note'] ?? '')), 403);

        return redirect()->route('sim.show', $sop);
    }

    public function finalize(Request $request, string $sop): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user->can('sop.sign'), 403);
        $data = $request->validate(['revision' => 'required|uuid', 'password' => 'required|string', 'pdf' => 'required|file|mimes:pdf|max:8192']);
        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi Direktur tidak cocok.']);
        }
        DB::transaction(function () use ($sop, $user, $data) {
            $document = $this->visible()->where('id', $sop)->firstOrFail();
            $id = $document->current_revision_id;
            abort_unless($document->status === 'APPROVED' && $id === $data['revision'], 409);
            abort_unless(DB::table('approval_steps')->where('revision_id', $id)->where('role', 'direktur')
                ->where('decision', 'setuju')->where('approver_user_id', $user->id)->exists(), 403);
            $template = DB::table('sop_revisions')->where('id', $id)->value('template_id');
            $slot = DB::table('signature_slots')->where('template_id', $template)->where('role', 'direktur')->firstOrFail();
            abort_unless(in_array('simulation', json_decode($slot->allowed_modes, true), true), 403);
            $pdf = $data['pdf']->getContent();
            DB::table('signature_logs')->insert(['id' => (string) Str::uuid(), 'revision_id' => $id,
                'slot_id' => $slot->id,
                'signer_user_id' => $user->id, 'signer_snapshot' => json_encode(['name' => $user->name, 'unit' => $user->organization_code]),
                'sign_mode' => 'simulation', 'sign_uuid' => (string) Str::uuid(), 'signed_at' => now('UTC')]);
            DB::table('sop_revisions')->where('id', $id)->update(['revision_status' => 'PUBLISHED', 'published_at' => now(),
                'final_pdf' => base64_encode($pdf), 'sha256_hash' => hash('sha256', $pdf)]);
            DB::table('sops')->where('id', $sop)->update(['status' => 'PUBLISHED', 'effective_date' => today(), 'updated_at' => now()]);
            $this->audit($id, 'published');
        });

        return redirect()->route('sim.show', $sop);
    }

    public function pdf(string $sop): Response
    {
        $document = $this->visible()->where('id', $sop)->where('status', 'PUBLISHED')->firstOrFail();
        $revision = DB::table('sop_revisions')->where('id', $document->current_revision_id)->firstOrFail();

        return response(base64_decode($revision->final_pdf, true), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="SOP-'.$revision->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    private function audit(string $revision, string $action): void
    {
        DB::table('audit_trails')->insert(['entity_type' => 'sop_revision', 'entity_id' => $revision,
            'actor_id' => $this->user()->id, 'action' => $action, 'metadata' => '{}', 'created_at' => now('UTC')]);
    }
}
