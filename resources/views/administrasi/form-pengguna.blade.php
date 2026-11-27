@extends('layouts.app')

@php($title = $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('title', $title)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Administrasi'],
        ['label' => 'Pengguna', 'route' => 'admin.users.index'],
        ['label' => $title],
    ]])
@endsection

@section('content')
    <h1>{{ $title }}</h1>
    <form class="row g-3" method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($user->exists)
            @method('PATCH')
        @endif

        @foreach (['name' => 'Nama', 'username' => 'Username', 'email' => 'Email'] as $field => $label)
            <div class="col-md-6">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}"
                    type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="255" value="{{ old($field, $user->{$field}) }}" required>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach

        <div class="col-md-6">
            <label for="is_active" class="form-label">Status</label>
            @php($editingOwnAccount = $user->is(Auth::guard('web')->user()))
            <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active" @disabled($editingOwnAccount)>
                <option value="1" @selected($editingOwnAccount || old('is_active', $user->is_active))>Aktif</option>
                <option value="0" @selected(! $editingOwnAccount && ! old('is_active', $user->is_active))>Nonaktif</option>
            </select>
            @if ($editingOwnAccount)
                <input type="hidden" name="is_active" value="1">
            @endif
            @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @if ($user->exists)
            <input type="hidden" name="role_ids[]" value="">
            <fieldset class="col-12">
                <legend class="form-label fs-6">Peran</legend>
                @php($selectedRoles = (array) old('role_ids', $user->roles->modelKeys()))
                @forelse ($roles as $role)
                    @php($keepSuperAdmin = $editingOwnAccount && in_array($role->name, \App\Models\User::ADMIN_ROLES, true) && $user->roles->contains($role))
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="role_ids[]" id="role-{{ $role->uuid }}" value="{{ $role->uuid }}"
                            @checked($keepSuperAdmin || in_array($role->uuid, $selectedRoles, true)) @disabled($keepSuperAdmin)>
                        <label class="form-check-label" for="role-{{ $role->uuid }}">{{ $role->name }}</label>
                        @if ($keepSuperAdmin)
                            <input type="hidden" name="role_ids[]" value="{{ $role->uuid }}">
                        @endif
                    </div>
                @empty
                    <p class="form-text">Belum ada peran yang tersedia.</p>
                @endforelse
                @if ($roleError = $errors->first('role_ids') ?: $errors->first('role_ids.*'))
                    <div class="invalid-feedback d-block" role="alert">{{ $roleError }}</div>
                @endif
            </fieldset>
        @endif

        <div class="col-md-6">
            <label for="password" class="form-label">Kata sandi</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password"
                autocomplete="new-password" minlength="12" maxlength="72" aria-describedby="password-help" @required(! $user->exists)>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text" id="password-help">{{ $user->exists ? 'Isi minimal 12 karakter untuk mengganti kata sandi.' : 'Minimal 12 karakter.' }}</div>
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Ulangi kata sandi</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation"
                autocomplete="new-password" minlength="12" maxlength="72" @required(! $user->exists)>
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </form>
@endsection
