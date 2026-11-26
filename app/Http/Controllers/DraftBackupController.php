<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DraftBackupController extends Controller
{
    private function drafts(Request $request): Builder
    {
        return DB::table('draft_backups')->where('session_key', hash('sha256', $request->session()->getId()));
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->drafts($request)->orderByDesc('updated_at')->get(['draft_key', 'title']));
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $draft = $this->drafts($request)->where('draft_key', $key)->firstOrFail();

        return response()->json(['data' => json_decode($draft->content), 'version' => $draft->version]);
    }

    public function save(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:0'],
            'data' => ['required', 'array:title,number,revision,unit,organization,definition,purpose,policy,procedure,related'],
            'data.title' => ['present', 'string', 'max:180'],
            'data.number' => ['present', 'string', 'max:55'],
            'data.unit' => ['present', 'string', 'max:110'],
            'data.organization' => ['required', 'string', 'max:180'],
            'data.revision' => ['required', 'integer', 'min:1'],
            'data.definition' => ['present', 'string', 'max:30000'],
            'data.purpose' => ['present', 'string', 'max:30000'],
            'data.policy' => ['present', 'string', 'max:30000'],
            'data.related' => ['present', 'string', 'max:30000'],
            'data.procedure' => ['required', 'array:ops'],
            'data.procedure.ops' => ['required', 'list', 'max:5000'],
            'data.procedure.ops.*' => ['required', 'array:insert,attributes'],
            'data.procedure.ops.*.insert' => ['present', 'string', 'max:30000'],
            'data.procedure.ops.*.attributes' => ['sometimes', 'array:bold,italic,underline,list'],
            'data.procedure.ops.*.attributes.bold' => ['sometimes', 'boolean'],
            'data.procedure.ops.*.attributes.italic' => ['sometimes', 'boolean'],
            'data.procedure.ops.*.attributes.underline' => ['sometimes', 'boolean'],
            'data.procedure.ops.*.attributes.list' => ['sometimes', 'in:ordered,bullet'],
        ]);

        $version = (int) $validated['version'];
        $values = [
            'version' => $version + 1,
            'title' => $validated['data']['title'],
            'content' => json_encode($validated['data'], JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];
        if ($version === 0) {
            $saved = DB::table('draft_backups')->insertOrIgnore($values + [
                'session_key' => hash('sha256', $request->session()->getId()),
                'draft_key' => $key,
            ]);
        } else {
            $saved = $this->drafts($request)->where('draft_key', $key)->where('version', $version)->update($values);
        }

        abort_unless($saved, 409, 'Versi draf telah berubah atau sesi penyimpanan telah berakhir.');

        return response()->json(['version' => $values['version']]);
    }
}
