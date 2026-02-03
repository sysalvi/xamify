<?php

namespace App\Filament\Pages;

use App\Models\ExamSession;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LiveMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Live Monitor';

    protected static string $view = 'filament.pages.live-monitor';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $role = Auth::user()?->role;

        return in_array($role, ['admin', 'guru', 'pengawas'], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ExamSession::query()->with('exam'))
            ->poll('5s')
            ->headerActions([
                Action::make('clear_sessions')
                    ->label('Clear Sessions')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => \Illuminate\Support\Facades\DB::table('exam_sessions')->delete()),
            ])
            ->recordClasses(function (ExamSession $record) {
                if (in_array($record->status, ['locked', 'violation'], true)) {
                    return 'bg-red-100 animate-pulse';
                }

                return null;
            })
            ->columns([
                TextColumn::make('student_name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('student_class')
                    ->label('Kelas')
                    ->searchable(),
                TextColumn::make('room.name')
                    ->label('Ruang')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable(),
                TextColumn::make('access_code')
                    ->label('Kode Akses')
                    ->copyable()
                    ->searchable(),
                IconColumn::make('is_locked')
                    ->label('Kunci')
                    ->boolean()
                    ->state(fn (ExamSession $record) => in_array($record->status, ['locked', 'violation'], true)),
                TextColumn::make('last_violation_reason')
                    ->label('Reason')
                    ->placeholder('-')
                    ->limit(24),
                TextColumn::make('status_display')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (ExamSession $record) {
                        if (in_array($record->status, ['locked', 'violation'], true)) {
                            return 'Locked';
                        }

                        if (! $record->last_ping_at) {
                            return 'Offline';
                        }

                        $seconds = Carbon::now()->diffInSeconds($record->last_ping_at);

                        return $seconds <= 60 ? 'Online' : 'Offline';
                    })
                    ->color(function (ExamSession $record) {
                        if (in_array($record->status, ['locked', 'violation'], true)) {
                            return 'danger';
                        }

                        if (! $record->last_ping_at) {
                            return 'gray';
                        }

                        $seconds = Carbon::now()->diffInSeconds($record->last_ping_at);

                        return $seconds <= 60 ? 'success' : 'gray';
                    }),
                TextColumn::make('last_ping_at')
                    ->label('Last Ping')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('violation_count')
                    ->label('Pelanggaran')
                    ->sortable(),
            ])
            ->actions([
                Action::make('unlock')
                    ->label('Unlock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ExamSession $record) => in_array($record->status, ['locked', 'violation'], true))
                    ->action(function (ExamSession $record) {
                        $record->update([
                            'status' => 'online',
                            'last_ping_at' => now(),
                            'locked_at' => null,
                            'last_violation_reason' => null,
                        ]);
                    }),
            ]);
    }
}
