<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');
        $email = Rule::unique(User::class, 'email');
        $username = Rule::unique(User::class, 'username');

        if ($user) {
            $email->ignore($user);
            $username->ignore($user);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', $username],
            'email' => ['required', 'email', 'max:255', $email],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'regex:/\A[^\x00]{1,72}\z/', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'role_ids' => [Rule::excludeIf(! $user), 'sometimes', 'array', 'list'],
            'role_ids.*' => ['nullable', 'uuid', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Kata sandi maksimal 72 byte dan tidak boleh memuat karakter NUL.',
            'role_ids.*' => 'Pilihan peran tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'role_ids' => 'peran',
        ];
    }
}
