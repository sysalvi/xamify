<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $activeSessions = ExamSession::query()
            ->whereNotNull('last_ping_at')
            ->where('status', '!=', 'locked')
            ->where('last_ping_at', '>=', Carbon::now()->subMinute())
            ->count();

        return [
            Stat::make('Ujian', Exam::query()->count())
                ->description('Jumlah exam aktif & tersimpan')
                ->chart([2, 4, 6, 5, 7, 6, 8])
                ->color('success'),
            Stat::make('Kelas', Room::query()->count())
                ->description('Ruang/Kelas terdaftar')
                ->chart([1, 2, 3, 3, 4, 4, 5])
                ->color('info'),
            Stat::make('Users', User::query()->count())
                ->description('Admin, guru, pengawas')
                ->chart([3, 3, 4, 4, 4, 5, 5])
                ->color('warning'),
            Stat::make('Active Session', $activeSessions)
                ->description('Ping < 1 menit')
                ->chart([1, 2, 2, 3, 3, 2, 4])
                ->color('primary'),
        ];
    }
}
