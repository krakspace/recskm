<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-super-admin')]
#[Description('Buat akun Super Admin untuk database utama')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan perintah secara interaktif untuk memasukkan kata sandi.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $this->ask('Nama'),
            'username' => $this->ask('Username'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Kata sandi (minimal 12 karakter)', false),
            'password_confirmation' => $this->secret('Ulangi kata sandi', false),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'regex:/\A[^\x00]{1,72}\z/', 'confirmed'],
        ], [
            'password.regex' => 'Kata sandi maksimal 72 byte dan tidak boleh memuat karakter NUL.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($validator): User {
            $user = User::create($validator->validated() + ['is_active' => true]);
            $user->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web'));

            return $user;
        });

        $this->info('Akun Super Admin dibuat untuk '.$user->email.'.');

        return self::SUCCESS;
    }
}
