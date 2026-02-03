<?php

use App\Models\AppSetting;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Room;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

$isTestMode = function (): bool {
    return AppSetting::query()
        ->where('key', 'test_mode')
        ->value('value') === '1';
};

$isExtensionAllowed = function (?string $pingOverride = null): bool {
    $expected = AppSetting::query()
        ->where('key', 'ping_password')
        ->value('value');

    if (! $expected) {
        return false;
    }

    $pingQuery = $pingOverride ?? request()->query('xamify_ping');
    $pingHeader = request()->header('X-Xamify-Ping');
    $extHeader = request()->header('X-Xamify-Ext');

    if ($pingQuery && hash_equals($expected, (string) $pingQuery)) {
        return true;
    }

    if ($extHeader === '1' && $pingHeader && hash_equals($expected, (string) $pingHeader)) {
        return true;
    }

    return false;
};

$generateAccessCode = function (): string {
    $prefix = AppSetting::query()
        ->where('key', 'access_code_prefix')
        ->value('value') ?? 'UQD';

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    do {
        $suffix = '';

        for ($i = 0; $i < 3; $i += 1) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $code = strtoupper(trim($prefix)).'-'.$suffix;
    } while (ExamSession::query()->where('access_code', $code)->exists());

    return $code;
};

$renderLanding = function (): \Illuminate\View\View {
    $rooms = Room::query()->orderBy('name')->get();

    return view('test.landing', [
        'rooms' => $rooms,
        'roomIndex' => $rooms->pluck('id', 'name'),
    ]);
};

Route::get('/', function () use ($isTestMode, $isExtensionAllowed, $renderLanding) {
    $userAgent = request()->header('User-Agent', '');
    $pingQuery = request()->query('xamify_ping');

    if (Str::contains($userAgent, 'EXAMUQ-BROWSER')) {
        return $renderLanding();
    }

    if (($pingQuery || request()->header('X-Xamify-Ext') === '1') && $isExtensionAllowed($pingQuery)) {
        return $renderLanding();
    }

    if ($isTestMode()) {
        return $renderLanding();
    }

    return response()->view('blocked', [], 403);
});

Route::post('/test/confirm', function () use ($isTestMode, $isExtensionAllowed, $generateAccessCode) {
    if (! $isTestMode() && ! $isExtensionAllowed(request()->input('xamify_ping'))) {
        return redirect('/');
    }

    $pingQuery = request()->input('xamify_ping');
    $baseUrl = url('/');

    $validator = Validator::make(request()->all(), [
        'student_name' => ['required', 'string', 'max:255'],
        'room_id' => ['required', 'integer', 'exists:rooms,id'],
        'exam_token' => ['required', 'string', 'max:255'],
    ]);

    if ($validator->fails()) {
        $query = ['error' => 'required'];

        if ($pingQuery) {
            $query['xamify_ping'] = $pingQuery;
        }

        return redirect($baseUrl.'?'.http_build_query($query))
            ->withErrors($validator)
            ->withInput();
    }

    $data = $validator->validated();

    $exam = Exam::query()
        ->where('exam_token', $data['exam_token'])
        ->where('is_active', true)
        ->first();

    if (! $exam) {
        $query = ['error' => 'invalid_token'];

        if ($pingQuery) {
            $query['xamify_ping'] = $pingQuery;
        }

        return redirect($baseUrl.'?'.http_build_query($query))
            ->withErrors([
                'exam_token' => 'Token salah.',
            ])
            ->withInput();
    }

    if ($exam->available_at && $exam->available_at->isFuture()) {
        $query = ['error' => 'not_available'];

        if ($pingQuery) {
            $query['xamify_ping'] = $pingQuery;
        }

        return redirect($baseUrl.'?'.http_build_query($query))
            ->withErrors([
                'exam_token' => 'Ujian belum tersedia.',
            ])
            ->withInput();
    }

    if ($exam->expires_at && $exam->expires_at->isPast()) {
        $query = ['error' => 'expired'];

        if ($pingQuery) {
            $query['xamify_ping'] = $pingQuery;
        }

        return redirect($baseUrl.'?'.http_build_query($query))
            ->withErrors([
                'exam_token' => 'Ujian sudah berakhir.',
            ])
            ->withInput();
    }

    $room = Room::query()->find($data['room_id']);
    if (! $room) {
        $query = ['error' => 'invalid_room'];

        if ($pingQuery) {
            $query['xamify_ping'] = $pingQuery;
        }

        return redirect($baseUrl.'?'.http_build_query($query))
            ->withErrors([
                'room_id' => 'Kelas tidak valid.',
            ])
            ->withInput();
    }

    if ($exam->rooms()->exists() && ! $exam->rooms()->whereKey($room->id)->exists()) {
        $query = ['error' => 'room_not_allowed'];

        if ($pingQuery) {
            $query['xamify_ping'] = $pingQuery;
        }

        return redirect($baseUrl.'?'.http_build_query($query))
            ->withErrors([
                'room_id' => 'Ujian tidak tersedia untuk kelas ini.',
            ])
            ->withInput();
    }

    $pingPassword = AppSetting::query()
        ->where('key', 'ping_password')
        ->value('value');

    $deviceId = request()->ip().'-test';

    $session = ExamSession::query()->firstOrNew([
        'exam_id' => $exam->id,
        'student_name' => $data['student_name'],
        'student_class' => $room->name,
        'room_id' => $room->id,
        'device_id' => $deviceId,
    ]);

    if (! $session->exists || ! $session->access_code) {
        $session->access_code = $generateAccessCode();
    }

    if (! in_array($session->status, ['locked', 'violation'], true)) {
        $session->status = 'online';
    }

    $session->last_ping_at = now();
    $session->save();

    session()->put('test_exam', [
        'student_name' => $data['student_name'],
        'student_class' => $room->name,
        'exam_title' => $exam->title,
        'exam_link' => $exam->exam_link,
        'exam_token' => $exam->exam_token,
        'session_id' => $session->id,
        'ping_password' => $pingPassword,
        'access_code' => $session->access_code,
        'duration_seconds' => (int) $room->duration_seconds,
        'session_label' => $room->session_label,
    ]);

    return redirect('/test/confirm');
});

Route::post('/test/validate-token', function () use ($isTestMode, $isExtensionAllowed) {
    $pingQuery = request()->input('xamify_ping') ?: request()->query('xamify_ping');
    $isAllowed = $isTestMode() || $isExtensionAllowed($pingQuery);

    if (! $isAllowed) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $data = request()->validate([
        'exam_token' => ['required', 'string', 'max:255'],
        'room_id' => ['required', 'integer', 'exists:rooms,id'],
    ]);

    $exam = Exam::query()
        ->where('exam_token', $data['exam_token'])
        ->where('is_active', true)
        ->first();

    if (! $exam) {
        return response()->json([
            'message' => 'Token salah.',
        ], 422);
    }

    if ($exam->available_at && $exam->available_at->isFuture()) {
        return response()->json([
            'message' => 'Ujian belum tersedia.',
        ], 422);
    }

    if ($exam->expires_at && $exam->expires_at->isPast()) {
        return response()->json([
            'message' => 'Ujian sudah berakhir.',
        ], 422);
    }

    if ($exam->rooms()->exists() && ! $exam->rooms()->whereKey($data['room_id'])->exists()) {
        return response()->json([
            'message' => 'Ujian tidak tersedia untuk kelas ini.',
        ], 422);
    }

    return response()->json(['status' => 'ok']);
});

Route::get('/test/confirm', function () use ($isTestMode, $isExtensionAllowed) {
    if (! $isTestMode() && ! $isExtensionAllowed()) {
        return redirect('/');
    }

    $payload = session('test_exam');

    if (! $payload) {
        return redirect('/');
    }

    return view('test.confirm', [
        'data' => $payload,
    ]);
});

Route::post('/test/start', function () use ($isTestMode, $isExtensionAllowed) {
    if (! $isTestMode() && ! $isExtensionAllowed()) {
        return redirect('/');
    }

    if (! session()->has('test_exam')) {
        return redirect('/');
    }

    return redirect('/test/exam');
});

Route::get('/test/exam', function () use ($isTestMode, $isExtensionAllowed) {
    if (! $isTestMode() && ! $isExtensionAllowed()) {
        return redirect('/');
    }

    $payload = session('test_exam');

    if (! $payload) {
        return redirect('/');
    }

    return view('test.exam', [
        'data' => $payload,
    ]);
});
