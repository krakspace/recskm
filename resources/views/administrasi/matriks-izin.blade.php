@extends('layouts.app')

@section('title', 'Matriks Izin')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['breadcrumbs' => [
        ['label' => 'Beranda', 'route' => 'dashboard'],
        ['label' => 'Administrasi'],
        ['label' => 'Matriks Izin'],
    ]])
@endsection

@section('content')
    <div class="user-management permissions-page">
        <header class="user-management__header">
            <h1>Matriks Izin</h1>
            <a class="btn btn-outline-secondary" href="{{ route('admin.roles.index', ['from' => 'matrix']) }}">Daftar Role</a>
        </header>
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if ($selectedRole)
            <form class="user-management__panel" method="get" action="{{ route('admin.users.permissions.index') }}">
                <label for="matrix-role" class="form-label">Pilih Peran</label>
                <div class="d-flex flex-wrap gap-2">
                    <select class="form-select w-auto" id="matrix-role" name="role">
                        @foreach ($roles as $role)
                            <option value="{{ $role->uuid }}" @selected($role->is($selectedRole))>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-outline-secondary" type="submit">Tampilkan Izin</button></noscript>
                </div>
            </form>
            @include('administrasi.izin-peran', ['role' => $selectedRole])
        @else
            <p class="user-management__panel mb-0">Belum ada peran yang dapat diatur.</p>
        @endif
    </div>
@endsection

@if ($selectedRole)
    @push('scripts')
        <script>
            document.getElementById('matrix-role').addEventListener('change', (event) => {
                event.currentTarget.form.requestSubmit();
            });

            document.querySelectorAll('#permissions-form [data-permission-group]').forEach((groupCheckbox) => {
                const group = groupCheckbox.closest('tbody');
                const permissionCheckboxes = Array.from(group.querySelectorAll('input[name="pages[]"]'));

                function syncGroupCheckbox() {
                    const checkedCount = permissionCheckboxes.filter((checkbox) => checkbox.checked).length;
                    groupCheckbox.checked = checkedCount > 0 && checkedCount === permissionCheckboxes.length;
                    groupCheckbox.indeterminate = checkedCount > 0 && !groupCheckbox.checked;
                }

                groupCheckbox.addEventListener('change', () => {
                    permissionCheckboxes.forEach((checkbox) => {
                        checkbox.checked = groupCheckbox.checked;
                    });
                });

                group.addEventListener('change', syncGroupCheckbox);
                window.addEventListener('pageshow', syncGroupCheckbox);
                syncGroupCheckbox();
                groupCheckbox.hidden = permissionCheckboxes.length === 0;
            });
        </script>
    @endpush
@endif
