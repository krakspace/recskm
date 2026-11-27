@extends('layouts.app')

@section('title', 'Pengguna')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Administrasi'],
        ['label' => 'Pengguna'],
    ]])
@endsection

@section('content')
    <div class="user-management">
        <header class="user-management__header">
            <h1>Pengguna</h1>
            <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Tambah Pengguna</a>
        </header>
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="user-management__panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle user-management__table" aria-label="Daftar pengguna">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $account)
                            <tr>
                                <th scope="row">{{ $account->name }}</th>
                                <td>{{ $account->username }}</td>
                                <td>{{ $account->email }}</td>
                                <td>{{ $account->isAdministrator() ? 'Admin' : ($account->getRoleNames()->implode(', ') ?: 'User') }}</td>
                                <td>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a class="btn btn-outline-secondary" href="{{ route('admin.users.edit', $account) }}">Edit</a>
                                        @unless ($account->is(Auth::guard('web')->user()))
                                            @if ($account->is_active)
                                                <form method="post" action="{{ route('admin.impersonation.store', $account) }}">
                                                    @csrf
                                                    <button class="btn btn-outline-secondary" type="submit">Masuk sebagai</button>
                                                </form>
                                            @endif
                                            <form method="post" action="{{ route('admin.users.destroy', $account) }}" onsubmit="return confirm('Hapus akun pengguna ini secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" type="submit">Hapus</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Belum ada pengguna pada halaman ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="user-management__footer">{{ $users->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>

    </div>
@endsection
