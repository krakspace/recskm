
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | Sistem Management</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-[#edf2f5] text-slate-900 antialiased">
    <main class="grid min-h-dvh place-items-center p-4 sm:p-8">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5 lg:grid-cols-[1fr_1.08fr]">
            <aside class="relative isolate flex flex-col justify-between gap-10 overflow-hidden bg-[#102b36] p-7 text-white sm:p-10 lg:min-h-[570px] lg:p-12">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_bottom_right,#216759,transparent_72%)]"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -right-36 -bottom-40 -z-10 hidden size-120 rounded-full border border-teal-200/20 shadow-[0_0_0_36px_#99f6e414,0_0_0_72px_#99f6e40a] lg:block"></div>

                <div class="flex items-center gap-3 text-sm font-semibold sm:text-base">
                    <span class="grid size-10 place-items-center rounded-xl bg-teal-200 font-bold tracking-tight text-[#102b36]" aria-hidden="true">SM</span>
                    <span>Sistem Management</span>
                </div>

                <div class="hidden max-w-xs pb-4 lg:block">
                    <p class="text-4xl leading-tight font-semibold tracking-tight">Standar kerja dalam satu tempat.</p>
                    <p class="mt-4 text-sm leading-6 text-slate-200">Akses dashboard dan kelola dokumen SOP dari satu ruang kerja.</p>
                </div>
            </aside>

            <section aria-labelledby="login-title" class="grid place-items-center px-6 py-10 sm:px-12 lg:py-16">
                <div class="w-full max-w-sm">
                    <h1 id="login-title" class="text-3xl font-bold tracking-tight sm:text-4xl">Masuk ke akun</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Masukkan email dan kata sandi akun kamu.</p>

                    <form action="{{ route('account.login.store') }}" method="post" class="mt-8 flex flex-col gap-5">
                        @csrf
                        @error('email') <p role="alert">{{ $message }}</p> @enderror
                        @error('password') <p role="alert">{{ $message }}</p> @enderror
                        <div class="flex flex-col gap-2">
                            <label for="email" class="text-sm font-semibold">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@perusahaan.com" required
                                class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-base placeholder:text-slate-500 focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 focus:outline-none">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="password" class="text-sm font-semibold">Kata sandi</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required
                                    class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 pr-28 pl-4 text-base placeholder:text-slate-500 focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20 focus:outline-none">
                                <button type="button" id="password-toggle" aria-controls="password" hidden
                                    class="absolute inset-y-1 right-1 cursor-pointer rounded-lg px-3 text-sm font-semibold text-teal-800 hover:bg-teal-100 focus-visible:outline-2 focus-visible:outline-teal-700">Tampilkan</button>
                            </div>
                        </div>

                        <button type="submit" class="mt-2 h-12 cursor-pointer rounded-xl bg-[#12594f] px-5 text-sm font-semibold text-white transition-colors hover:bg-[#0e473f] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-700">
                            Masuk
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');

        passwordToggle.hidden = false;
        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';

            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.textContent = showPassword ? 'Sembunyikan' : 'Tampilkan';
        });
    </script>
</body>
</html>
