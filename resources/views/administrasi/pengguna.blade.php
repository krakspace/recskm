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
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="mb-0">Pengguna</h1>
        <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Tambah Pengguna</a>
    </div>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover align-middle">
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
                @forelse ($users as $user)
                    <tr>
                        <th scope="row">{{ $user->name }}</th>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->getRoleNames()->implode(', ') ?: '—' }}</td>
                        <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.edit', $user) }}" aria-label="Edit {{ $user->name }}">Edit</a>
                                @unless ($user->is(Auth::guard('web')->user()))
                                    @if ($user->is_active)
                                        <form method="post" action="{{ route('admin.impersonation.store', $user) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" type="submit" aria-label="Masuk sebagai {{ $user->name }}">Masuk sebagai</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun pengguna ini secara permanen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus {{ $user->name }}">Hapus</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links('pagination::bootstrap-5') }}
@endsection
