<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XAMIFY</title>
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
<body class="min-h-screen bg-slate-950 text-white">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-brandMagenta/30 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-brandOrange/30 blur-3xl"></div>

        <div class="mx-auto flex min-h-screen w-full max-w-4xl items-center justify-center px-6 py-12">
            <div class="w-full rounded-3xl border border-white/10 bg-slate-900/70 p-10 text-center shadow-2xl">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white text-3xl font-bold text-brandMagenta shadow-xl">
                    X
                </div>
                <p class="mt-6 inline-flex items-center rounded-full bg-brandOrange/20 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-brandOrange">
                    Akses Ditolak
                </p>
                <h1 class="mt-4 text-2xl font-semibold md:text-3xl">Gunakan Aplikasi XAMIFY-CLIENT</h1>
                <p class="mt-3 text-sm text-slate-300 md:text-base">
                    Halaman ujian hanya bisa diakses melalui XAMIFY Client atau ekstensi resmi.
                </p>
                <div class="mt-8 flex flex-col gap-3 text-xs text-slate-400 md:flex-row md:items-center md:justify-center">
                    <span>Pastikan proteksi ujian aktif sebelum masuk.</span>
                    <span class="hidden md:inline">•</span>
                    <span>Hubungi pengawas jika masih terkunci.</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
