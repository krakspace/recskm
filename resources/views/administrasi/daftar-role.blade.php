@extends('layouts.app')

@section('title', 'Daftar Role')

@php($fromMatrix = request()->query('from') === 'matrix')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Administrasi'],
        $fromMatrix
            ? ['label' => 'Matriks Izin', 'route' => 'admin.users.permissions.index']
            : ['label' => 'Pengguna', 'route' => 'admin.users.index'],
        ['label' => 'Daftar Role'],
    ]])
@endsection

@section('content')
    <div class="user-management">
        <header class="user-management__header">
            <h1>Daftar Role</h1>
            <a class="btn btn-outline-secondary" href="{{ route($fromMatrix ? 'admin.users.permissions.index' : 'admin.users.index') }}">Kembali</a>
        </header>
        @if (session('status'))
            <div class="alert alert-success mb-0" role="status">{{ session('status') }}</div>
        @endif

        <form class="user-management__panel" method="post" action="{{ route('admin.roles.store', $fromMatrix ? ['from' => 'matrix'] : []) }}">
            @csrf
            <label for="name" class="form-label">Nama peran baru</label>
            <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                maxlength="255" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-3" type="submit">Daftarkan Role</button>
        </form>

        <div class="user-management__panel">
            <div class="table-responsive">
                <table class="table align-middle user-management__table" aria-label="Peran terdaftar">
                    <thead>
                        <tr>
                            <th scope="col">Peran Terdaftar</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <th scope="row">{{ $role->name }}</th>
                                <td class="text-end">
                                    @if (in_array($role->name, \App\Models\User::ADMIN_ROLES, true))
                                        <span class="text-body-secondary">Bawaan</span>
                                    @else
                                        <form method="post" action="{{ route('admin.roles.destroy', ['role' => $role] + ($fromMatrix ? ['from' => 'matrix'] : [])) }}"
                                            onsubmit="return confirm('Hapus peran ini? Pengguna dengan peran ini akan kehilangan izin yang berasal dari peran tersebut.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit" aria-label="Hapus peran {{ $role->name }}">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2">Belum ada peran terdaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($roles->hasPages())
                <div class="user-management__footer">{{ $roles->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </div>
@endsection
