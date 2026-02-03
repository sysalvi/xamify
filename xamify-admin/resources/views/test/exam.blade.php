<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ujian - {{ $data['exam_title'] }}</title>
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
<body class="min-h-screen bg-slate-950 text-white" data-session-id="{{ $data['session_id'] }}" data-ping-password="{{ $data['ping_password'] }}" data-exam-link="{{ $data['exam_link'] }}" data-duration="{{ $data['duration_seconds'] ?? 0 }}">
    <div class="flex min-h-screen flex-col">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 bg-slate-900/80 px-6 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-brandOrange">Mode Test</p>
                <h1 class="mt-1 text-lg font-semibold">{{ $data['exam_title'] }}</h1>
            </div>
            <div class="text-right">
                <p class="text-sm font-medium">{{ $data['student_name'] }}</p>
                <p class="text-xs text-slate-300">{{ $data['student_class'] }}</p>
                <p class="mt-1 text-xs text-slate-400">Kode Akses: <span class="font-semibold text-brandOrange">{{ $data['access_code'] }}</span></p>
            </div>
            <button id="finish-button" class="rounded-xl bg-slate-700 px-4 py-2 text-xs font-semibold text-white opacity-60" disabled>
                Selesai
            </button>
        </header>

        <main class="flex-1 bg-slate-900">
            <iframe
                id="exam-frame"
                title="Ujian"
                src="{{ $data['exam_link'] }}"
                class="h-[calc(100vh-88px)] w-full border-0"
                allowfullscreen
            ></iframe>
        </main>
    </div>

    <div id="lock-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950 px-6 text-center">
        <div class="max-w-md rounded-2xl border border-white/10 bg-slate-900 p-6">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brandMagenta/20 text-2xl">🔒</div>
            <h2 class="mt-4 text-xl font-semibold">Sesi Dikunci</h2>
            <p class="mt-2 text-sm text-slate-300">Terindikasi pelanggaran. Hubungi pengawas untuk membuka kembali.</p>
            <p class="mt-4 text-xs uppercase tracking-[0.3em] text-brandOrange">Kode Akses</p>
            <p class="mt-2 text-lg font-semibold text-white">{{ $data['access_code'] }}</p>
        </div>
    </div>

    <div id="confirm-overlay" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950 px-6 text-center">
        <div class="max-w-md rounded-2xl border border-white/10 bg-slate-900 p-6">
            <h2 class="text-xl font-semibold">Konfirmasi Ujian</h2>
            <p class="mt-2 text-sm text-slate-300">Klik mulai untuk mengaktifkan fullscreen dan melanjutkan ujian.</p>
            <button id="confirm-start" class="mt-4 rounded-xl bg-brandOrange px-4 py-2 text-sm font-semibold text-white">
                Mulai Ujian
            </button>
        </div>
    </div>

    <script>
        const sessionId = Number(document.body.dataset.sessionId || 0);
        const pingPassword = document.body.dataset.pingPassword || '';
        const examLink = document.body.dataset.examLink || '';
        const lockOverlay = document.getElementById('lock-overlay');
        const confirmOverlay = document.getElementById('confirm-overlay');
        const confirmStart = document.getElementById('confirm-start');
        const examFrame = document.getElementById('exam-frame');
        const finishButton = document.getElementById('finish-button');
        const durationSeconds = Number(document.body.dataset.duration || 0);
        let isLocked = false;

        if (sessionId) {
            localStorage.setItem('xamify_session_id', String(sessionId));
        }

        if (pingPassword) {
            localStorage.setItem('xamify_ping_password', pingPassword);
        }

        const showLocked = () => {
            lockOverlay.classList.remove('hidden');
            lockOverlay.classList.add('flex');
        };

        const showConfirm = () => {
            confirmOverlay.classList.remove('hidden');
            confirmOverlay.classList.add('flex');
        };

        const hideConfirm = () => {
            confirmOverlay.classList.add('hidden');
            confirmOverlay.classList.remove('flex');
        };

        const lockForFullscreenExit = async () => {
            if (isLocked) return;
            isLocked = true;
            showLocked();

            try {
                await fetch('/api/violation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        reason: 'exit_fullscreen',
                    }),
                });
            } catch (error) {
                // ignore
            }
        };

        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement === null) {
                lockForFullscreenExit();
            }
        });

        confirmStart.addEventListener('click', async () => {
            try {
                if (document.fullscreenElement === null) {
                    await document.documentElement.requestFullscreen();
                }
            } catch (error) {
                // ignore
            }

            hideConfirm();
        });

        setInterval(async () => {
            try {
                const response = await fetch('/api/heartbeat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        ping_password: pingPassword,
                    }),
                });

                const payload = await response.json();

                if (payload.session_status === 'locked') {
                    if (!isLocked) {
                        isLocked = true;
                        showLocked();
                    }
                    return;
                }

                if (isLocked) {
                    isLocked = false;
                    lockOverlay.classList.add('hidden');
                    lockOverlay.classList.remove('flex');
                    showConfirm();
                }
            } catch (error) {
                // ignore
            }
        }, 10000);

        updateFinishButton();
        setInterval(updateFinishButton, 1000);

        const isReload = performance && performance.getEntriesByType('navigation')
            ? performance.getEntriesByType('navigation')[0]?.type === 'reload'
            : false;

        if (isReload) {
            showConfirm();
        }

        if (new URLSearchParams(window.location.search).get('confirm') === '1') {
            showConfirm();
        }
    </script>
</body>
</html>
        const sessionStartKey = `xamify_start_${sessionId}`;
        const setStartTimeIfMissing = () => {
            if (!sessionId || durationSeconds <= 0) {
                return null;
            }

            const existing = localStorage.getItem(sessionStartKey);
            if (existing) {
                return Number(existing);
            }

            const now = Date.now();
            localStorage.setItem(sessionStartKey, String(now));
            return now;
        };

        const formatTime = (seconds) => {
            const min = Math.floor(seconds / 60);
            const sec = Math.floor(seconds % 60);
            return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        };

        const updateFinishButton = () => {
            if (!durationSeconds) {
                finishButton.disabled = false;
                finishButton.classList.remove('opacity-60');
                finishButton.textContent = 'Selesai';
                return;
            }

            const startTime = setStartTimeIfMissing();
            if (!startTime) {
                return;
            }

            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const remaining = durationSeconds - elapsed;

            if (remaining <= 0) {
                finishButton.disabled = false;
                finishButton.classList.remove('opacity-60');
                finishButton.textContent = 'Selesai';
                return;
            }

            finishButton.disabled = true;
            finishButton.classList.add('opacity-60');
            finishButton.textContent = `Selesai (${formatTime(remaining)})`;
        };

        finishButton.addEventListener('click', async () => {
            try {
                await fetch('/api/finish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        ping_password: pingPassword,
                    }),
                });
            } catch (error) {
                // ignore
            }

            localStorage.removeItem(sessionStartKey);
            localStorage.removeItem('xamify_session_id');
            localStorage.removeItem('xamify_ping_password');
            window.location.href = '/';
        });
