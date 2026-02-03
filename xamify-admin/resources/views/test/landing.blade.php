<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XAMIFY Test Mode</title>
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
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-brandOrange/10 via-white to-brandMagenta/10 text-slate-900">
    <div class="relative overflow-hidden">
        <div class="absolute -top-20 -right-24 h-64 w-64 rounded-full bg-brandMagenta/20 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-brandOrange/20 blur-3xl"></div>

        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col items-center justify-center px-6 py-12">
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold tracking-wider text-brandMagenta md:text-5xl">XAMIFY</h1>
                <p class="mt-2 text-sm uppercase tracking-[0.35em] text-slate-500">Aplikasi Ujian Berbasis WEB</p>
                <p class="mt-4 text-lg font-medium text-slate-700">Silahkan lengkapi data</p>
            </div>

            <div class="glass w-full max-w-xl rounded-2xl border border-white/40 p-6 shadow-xl md:p-8">
                <div id="token-error" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 hidden"></div>
                <form method="POST" action="/test/confirm" class="space-y-5" id="exam-form">
                    @csrf
                    <input type="hidden" name="xamify_ping" value="{{ request('xamify_ping') }}" />
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nama Siswa</label>
                        <input
                            name="student_name"
                            value="{{ old('student_name') }}"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brandOrange focus:outline-none focus:ring-2 focus:ring-brandOrange/30"
                            placeholder="Contoh: Andi Pratama"
                            required
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Ruang/Kelas</label>
                        <select
                            name="room_id"
                            id="room-id"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brandOrange focus:outline-none focus:ring-2 focus:ring-brandOrange/30"
                            required
                        >
                            <option value="">Pilih Ruang/Kelas</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Token Ujian</label>
                        <input
                            name="exam_token"
                            value="{{ old('exam_token') }}"
                            id="exam-token"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm uppercase tracking-widest shadow-sm focus:border-brandMagenta focus:outline-none focus:ring-2 focus:ring-brandMagenta/30"
                            placeholder="MTK123"
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        class="group flex w-full items-center justify-center gap-2 rounded-xl bg-brandMagenta px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-brandMagenta/90"
                    >
                        Lanjutkan
                        <span class="transition group-hover:translate-x-1">→</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('exam-form');
        const tokenInput = document.getElementById('exam-token');
        const roomSelect = document.getElementById('room-id');
        const tokenError = document.getElementById('token-error');
        const params = new URLSearchParams(window.location.search);
        const errorParam = params.get('error');
        const pingValue = params.get('xamify_ping') || '';
        const csrfToken = document.querySelector('input[name="_token"]').value;
        const showError = (message) => {
            tokenError.textContent = message;
            tokenError.classList.remove('hidden');
        };

        if (errorParam === 'required') showError('Token wajib diisi.');
        if (errorParam === 'invalid_token') showError('Token salah.');
        if (errorParam === 'invalid_room') showError('Kelas tidak valid.');
        if (errorParam === 'room_not_allowed') showError('Ujian tidak tersedia untuk kelas ini.');
        if (errorParam === 'not_available') showError('Ujian belum tersedia.');
        if (errorParam === 'expired') showError('Ujian sudah berakhir.');

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!roomSelect.value) {
                showError('Kelas wajib dipilih.');
                roomSelect.focus();
                return;
            }

            if (!tokenInput.value.trim()) {
                showError('Token wajib diisi.');
                tokenInput.focus();
                return;
            }

            fetch('/test/validate-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    exam_token: tokenInput.value.trim(),
                    room_id: Number(roomSelect.value),
                    xamify_ping: pingValue,
                }),
            })
                .then((response) => {
                    if (response.ok) {
                        form.submit();
                        return;
                    }

                    return response.json().then((payload) => {
                        showError(payload.message || 'Token salah.');
                    });
                })
                .catch(() => {
                    showError('Token salah.');
                });
        });
    </script>
</body>
</html>
