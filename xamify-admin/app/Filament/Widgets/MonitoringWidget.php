<?php

namespace App\Filament\Widgets;

use App\Models\ExamSession;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class MonitoringWidget extends BaseWidget
{
    protected static ?string $heading = 'Monitoring Sesi Ujian';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = '5s';

    public function table(Table $table): Table
    {
        return $table
            ->query(ExamSession::query()->with('exam'))
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
                        ]);
                    }),
            ]);
    }
}
