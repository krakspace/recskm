<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DraftBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateSuperAdmin();
    }

    private function payload(): array
    {
        return [
            'version' => 0,
            'data' => [
                'title' => 'Draf awal', 'number' => '', 'revision' => '1', 'unit' => '',
                'organization' => 'RS', 'definition' => '', 'purpose' => '', 'policy' => '', 'related' => '',
                'procedure' => ['ops' => [['insert' => "  Langkah satu\n", 'attributes' => ['bold' => true]], ['insert' => "\n"]]],
            ],
        ];
    }

    public function test_session_can_save_reopen_and_update_without_overwriting_newer_changes(): void
    {
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
        $url = route('draft-backups.save', 'draft-a');
        $payload = $this->payload();
        $this->putJson($url, $payload)->assertOk()->assertJsonPath('version', 1);
        $this->getJson(route('draft-backups.index'))->assertJsonPath('0.title', 'Draf awal');
        $this->getJson($url)->assertExactJson(['data' => $payload['data'], 'version' => 1]);
        $payload['version'] = 1;
        $payload['data']['title'] = 'Versi kedua';
        $this->putJson($url, $payload)->assertOk()->assertJsonPath('version', 2);
        $this->putJson($url, $this->payload())->assertConflict();
        $payload['data']['title'] = 'Salinan usang';
        $this->putJson($url, $payload)->assertConflict();
        $this->getJson($url)->assertJsonPath('data.title', 'Versi kedua');
    }

    public function test_other_session_cannot_list_read_or_update_a_draft(): void
    {
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
        $url = route('draft-backups.save', 'draft-a');
        $payload = $this->payload();
        $this->putJson($url, $payload)->assertOk();
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
        $this->getJson(route('draft-backups.index'))->assertExactJson([]);
        $this->getJson($url)->assertNotFound();
        $payload['version'] = 1;
        $this->putJson($url, $payload)->assertConflict();
    }

    public function test_editor_rejects_embeds_and_unknown_formatting(): void
    {
        $payload = $this->payload();
        $payload['data']['procedure']['ops'] = [['insert' => ['image' => 'example'], 'attributes' => ['link' => 'example']]];
        $this->putJson(route('draft-backups.save', 'draft-a'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors(['data.procedure.ops.0.insert', 'data.procedure.ops.0.attributes']);
        $this->assertDatabaseCount('draft_backups', 0);
    }
}
