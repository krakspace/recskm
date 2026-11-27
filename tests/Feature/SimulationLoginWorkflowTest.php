<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SopSimulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class SimulationLoginWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sop-login-'.Str::uuid();
        mkdir($this->directory);
        $this->app->useDatabasePath($this->directory);
        $this->artisan('sop:simulate')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unlink($this->directory.DIRECTORY_SEPARATOR.'sop-simulation.sqlite');
        rmdir($this->directory);
    }

    private function loginAs(string $username): void
    {
        $response = $this->post(route('account.login.store'), ['email' => $username.'@example.invalid', 'password' => SopSimulationSeeder::PASSWORD])
            ->assertRedirect(route('sim.index'));
        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
    }

    public function test_single_form_routes_each_account_and_clears_the_previous_login(): void
    {
        $user = User::factory()->create();
        $this->get(route('login'))->assertSee('action="'.route('account.login.store').'"', false);

        $response = $this->post(route('account.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->assertAuthenticatedAs($user, 'web');

        $this->loginAs('drafter');
        $this->get(route('sim.index'))->assertOk()->assertSeeText('Nadia Putri');
        $this->assertGuest('web');

        $response = $this->post(route('account.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing(Auth::guard('simulation')->getName());
        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->get(route('sim.index'))->assertRedirect(route('login'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_primary_account_takes_precedence_when_an_email_exists_in_both_databases(): void
    {
        $user = User::factory()->create(['email' => 'drafter@example.invalid']);

        $this->post(route('account.login.store'), ['email' => $user->email, 'password' => SopSimulationSeeder::PASSWORD])
            ->assertSessionHasErrors('email');
        $this->assertGuest('web');

        $this->post(route('account.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_seeded_accounts_complete_a_rejected_then_published_revision(): void
    {
        $this->get(route('sim.index'))->assertRedirect(route('login'));
        $this->post(route('sim.login'), ['email' => 'drafter@example.invalid', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->loginAs('drafter');
        $this->get(route('sim.index'))->assertOk()->assertSeeText('Nadia Putri');
        $location = $this->post(route('sim.create'))->assertRedirect()->headers->get('Location');
        $id = basename($location);
        $edit = $this->get($location)->assertOk();
        $revision = $edit->viewData('revision');
        $data = $edit->viewData('data');
        foreach (['definition', 'purpose', 'policy', 'related'] as $field) {
            $data[$field] = 'Contoh '.$field;
        }
        $data['procedure'] = ['ops' => [['insert' => "Catat dokumen.\n"]]];
        $payload = ['revision' => $revision->id, 'token' => hash('sha256', $revision->content), 'data' => $data, 'submit' => false];
        $this->withCredentials();
        $payload['token'] = $this->putJson(route('sim.save', $id), $payload)->assertOk()->json('token');
        $payload['submit'] = true;
        $this->putJson(route('sim.save', $id), $payload)->assertOk();

        $this->loginAs('atasan');
        $decision = ['revision' => $revision->id, 'decision' => 'setuju'];
        $this->post(route('sim.review', $id), $decision)->assertForbidden();
        $this->loginAs('kepala-unit');
        $this->post(route('sim.review', $id), ['revision' => $revision->id, 'decision' => 'tolak', 'note' => 'Tambahkan tujuan'])->assertRedirect();
        $this->loginAs('drafter');
        $this->get($location)->assertSeeText('Tambahkan tujuan');
        $this->post(route('sim.revise', $id), ['revision' => $revision->id])->assertRedirect($location);
        $revised = $this->get($location)->viewData('revision');
        $this->assertEquals(2, $revised->revision_number);
        $this->putJson(route('sim.save', $id), $payload)->assertConflict();
        $payload['data']['purpose'] = 'Mencatat serah terima dokumen unit.';
        $payload['revision'] = $revised->id;
        $payload['token'] = hash('sha256', $revised->content);
        $this->putJson(route('sim.save', $id), $payload)->assertOk();
        foreach (['kepala-unit', 'atasan', 'direktur'] as $username) {
            $this->loginAs($username);
            $this->post(route('sim.review', $id), ['revision' => $revised->id, 'decision' => 'setuju'])->assertRedirect();
        }
        $this->get($location)->assertSeeText('Finalisasi Direktur');
        $bytes = file_get_contents(glob(public_path('modul-sop-pdf/contoh/*.pdf'))[0]);
        $final = ['revision' => $revised->id, 'pdf' => UploadedFile::fake()->createWithContent('sop.pdf', $bytes)];
        $this->post(route('sim.finalize', $id), $final + ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post(route('sim.finalize', $id), $final + ['password' => SopSimulationSeeder::PASSWORD])->assertRedirect($location);
        $this->loginAs('viewer');
        $this->get(route('sim.index'))->assertSeeText('PUBLISHED');
        $this->assertSame($bytes, $this->get(route('sim.pdf', $id))->assertOk()->getContent());
        $this->post(route('sim.create'))->assertForbidden();
        $this->post(route('sim.logout'))->assertRedirect(route('login'));
        $this->get(route('sim.pdf', $id))->assertRedirect(route('login'));
    }
}
