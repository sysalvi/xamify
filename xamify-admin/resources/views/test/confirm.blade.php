<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfirmasi Ujian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandOrange: '#fd7818',
                        brandMagenta: '#9e236f',
                    },
                    fontFamily: {
                        sans: ['Space Grotesk', 'ui-sans-serif', 'system-ui'],
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-brandOrange/10 via-white to-brandMagenta/10 text-slate-900">
    <div class="mx-auto flex min-h-screen w-full max-w-5xl flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-2xl rounded-2xl bg-white/90 p-8 shadow-xl">
            <div class="mb-6">
                <p class="text-sm uppercase tracking-[0.3em] text-brandMagenta">Konfirmasi</p>
                <h1 class="mt-3 text-3xl font-semibold">Halo, {{ $data['student_name'] }}</h1>
                <p class="mt-2 text-slate-600">Silakan cek kembali data sebelum memulai ujian.</p>
            </div>

            <div class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Nama</span>
                    <span class="text-sm font-medium text-slate-900">{{ $data['student_name'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Kelas</span>
                    <span class="text-sm font-medium text-slate-900">{{ $data['student_class'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Ujian</span>
                    <span class="text-sm font-medium text-slate-900">{{ $data['exam_title'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Token</span>
                    <span class="text-sm font-medium uppercase tracking-widest text-brandMagenta">{{ $data['exam_token'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Sesi</span>
                    <span class="text-sm font-medium text-slate-900">{{ $data['session_label'] ?? '-' }}</span>
                </div>
            </div>

            <form method="POST" action="/test/start" class="mt-6" id="start-form">
                @csrf
                <button
                    type="button"
                    id="start-button"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-brandOrange px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-brandOrange/90"
                >
                    Mulai Ujian
                    <span class="transition group-hover:translate-x-1">→</span>
                </button>
            </form>
            <a href="/" class="mt-4 block text-center text-sm text-slate-500 hover:text-brandMagenta">
                Kembali ke halaman awal
            </a>
        </div>
    </div>
    <script>
        const startButton = document.getElementById('start-button');
        const startForm = document.getElementById('start-form');

        startButton.addEventListener('click', async () => {
            try {
                if (document.fullscreenElement === null) {
                    await document.documentElement.requestFullscreen();
                }
            } catch (error) {
                // Ignore fullscreen errors.
            }

            startForm.submit();
        });
    </script>
</body>
</html>
