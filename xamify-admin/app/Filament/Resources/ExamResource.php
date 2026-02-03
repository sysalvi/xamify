<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Models\Exam;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Nama Ujian')
                    ->required()
                    ->maxLength(255),
                Textarea::make('exam_link')
                    ->label('URL Google Form')
                    ->required()
                    ->rows(3),
                TextInput::make('exam_token')
                    ->label('Token Ujian')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->default(fn () => strtoupper(Str::random(6)))
                    ->suffixAction(
                        FormAction::make('generate')
                            ->label('Generate')
                            ->icon('heroicon-m-arrow-path')
                            ->action(function (Set $set) {
                                $set('exam_token', strtoupper(Str::random(6)));
                            })
                    ),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                DatePicker::make('available_at')
                    ->label('Mulai Tanggal')
                    ->nullable(),
                DatePicker::make('expires_at')
                    ->label('Berakhir Tanggal')
                    ->nullable(),
                Select::make('rooms')
                    ->label('Ruang/Kelas')
                    ->multiple()
                    ->relationship('rooms', 'name')
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Ujian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('exam_token')
                    ->label('Token')
                    ->copyable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('available_at')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Berakhir')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Update')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role !== 'pengawas';
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->role !== 'pengawas';
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->role !== 'pengawas';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
