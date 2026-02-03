<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentExamController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'student_class' => ['nullable', 'string', 'max:255'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'exam_token' => ['required', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $exam = Exam::query()
            ->where('exam_token', $data['exam_token'])
            ->where('is_active', true)
            ->first();

        if (! $exam) {
            return response()->json([
                'message' => 'Token ujian tidak valid atau ujian tidak aktif.',
            ], 404);
        }

        if ($exam) {
            if ($exam->available_at && $exam->available_at->isFuture()) {
                return response()->json([
                    'message' => 'Ujian belum tersedia.',
                ], 403);
            }

            if ($exam->expires_at && $exam->expires_at->isPast()) {
                return response()->json([
                    'message' => 'Ujian sudah berakhir.',
                ], 403);
            }
        }

        $roomId = $data['room_id'] ?? null;
        $roomName = $data['student_class'] ?? null;
        $room = null;

        if ($roomId) {
            $room = Room::query()->find($roomId);
            $roomName = $room?->name;

            if (! $room) {
                return response()->json([
                    'message' => 'Kelas tidak valid.',
                ], 422);
            }

            if ($exam && $exam->rooms()->exists() && ! $exam->rooms()->whereKey($roomId)->exists()) {
                return response()->json([
                    'message' => 'Ujian tidak tersedia untuk kelas ini.',
                ], 403);
            }
        }

        if (! $roomName) {
            return response()->json([
                'message' => 'Kelas tidak valid.',
            ], 422);
        }

        $deviceId = $data['device_id'] ?? $request->ip();

        $session = ExamSession::query()->firstOrNew([
            'exam_id' => $exam->id,
            'student_name' => $data['student_name'],
            'student_class' => $roomName,
            'room_id' => $roomId,
            'device_id' => $deviceId,
        ]);

        if (! $session->exists || ! $session->access_code) {
            $session->access_code = $this->generateAccessCode();
        }

        if ($session->status !== 'locked') {
            $session->status = 'online';
        }

        $session->last_ping_at = now();
        $session->save();

        $pingPassword = AppSetting::query()
            ->where('key', 'ping_password')
            ->value('value');

        return response()->json([
            'session_id' => $session->id,
            'exam_link' => $exam->exam_link,
            'ping_password' => $pingPassword,
            'access_code' => $session->access_code,
            'duration_seconds' => $room ? (int) $room->duration_seconds : 0,
        ]);
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'ping_password' => ['required', 'string'],
        ]);

        $expectedPassword = AppSetting::query()
            ->where('key', 'ping_password')
            ->value('value');

        if ($expectedPassword !== $data['ping_password']) {
            return response()->json([
                'message' => 'Ping password tidak valid.',
            ], 403);
        }

        DB::table('exam_sessions')
            ->where('id', $data['session_id'])
            ->update([
                'last_ping_at' => now(),
                'status' => DB::raw("IF(status = 'locked', status, 'online')"),
                'updated_at' => now(),
            ]);

        $status = DB::table('exam_sessions')
            ->where('id', $data['session_id'])
            ->value('status');

        return response()->json([
            'status' => 'ok',
            'session_status' => $status,
        ]);
    }

    public function violation(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        ExamSession::query()
            ->where('id', $data['session_id'])
            ->update([
                'status' => 'locked',
                'violation_count' => DB::raw('violation_count + 1'),
                'last_violation_reason' => $data['reason'] ?? 'unknown',
                'locked_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['status' => 'ok']);
    }

    public function handshakeExtension(Request $request)
    {
        if ($request->header('X-Xamify-Ext') !== '1') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (app()->environment('production')) {
            $ip = $request->ip();
            if (! in_array($ip, ['127.0.0.1', '::1'], true)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $pingPassword = AppSetting::query()
            ->where('key', 'ping_password')
            ->value('value');

        return response()->json([
            'ping_password' => $pingPassword,
        ]);
    }

    public function finish(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'ping_password' => ['required', 'string'],
        ]);

        $expectedPassword = AppSetting::query()
            ->where('key', 'ping_password')
            ->value('value');

        if ($expectedPassword !== $data['ping_password']) {
            return response()->json([
                'message' => 'Ping password tidak valid.',
            ], 403);
        }

        ExamSession::query()->where('id', $data['session_id'])->delete();

        return response()->json(['status' => 'ok']);
    }

    protected function generateAccessCode(): string
    {
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
    }
}
