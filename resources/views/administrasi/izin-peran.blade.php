<section class="user-management__panel" aria-labelledby="permissions-heading">
    <header class="user-management__header">
        <h2 id="permissions-heading">{{ $role->name }}</h2>
    </header>

    @if ($errors->has('pages') || $errors->has('pages.*'))
        <p class="alert alert-danger" role="alert">Pilihan halaman tidak valid.</p>
    @endif

    <form method="post" action="{{ route('admin.users.permissions.update', $role) }}">
        @csrf
        @method('PUT')
        <div class="table-responsive">
            <table class="table table-hover align-middle user-management__table user-management__matrix">
                <thead>
                    <tr>
                        <th scope="col">Halaman</th>
                        <th scope="col" class="text-center">Akses</th>
                    </tr>
                </thead>
                @foreach ($groups as $section => $items)
                    <tbody>
                        <tr class="table-light"><th scope="rowgroup" colspan="2">{{ $section }}</th></tr>
                        @foreach ($items as $label => $page)
                            @php
                                $id = 'page-'.$loop->parent->index.'-'.$loop->index;
                                $permission = \App\Http\Middleware\EnsurePageAccess::permission($page);
                            @endphp
                            <tr>
                                <th scope="row"><label for="{{ $id }}">{{ $label }}</label></th>
                                <td class="text-center">
                                    <input class="form-check-input" type="checkbox" name="pages[]" value="{{ $page }}" id="{{ $id }}"
                                        @checked(in_array($permission, $selectedPermissions, true))>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>

        <div class="user-management__footer">
            <button class="btn btn-primary" type="submit">Simpan Izin</button>
        </div>
    </form>
</section>
