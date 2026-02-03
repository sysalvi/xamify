<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Ruang/Kelas';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $role = Auth::user()?->role;

        return in_array($role, ['admin', 'guru'], true);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Ruang/Kelas')
                    ->required()
                    ->maxLength(255),
                TextInput::make('session_label')
                    ->label('Sesi')
                    ->required()
                    ->maxLength(50),
                TextInput::make('duration_seconds')
                    ->label('Waktu (detik)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ruang/Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session_label')
                    ->label('Sesi')
                    ->sortable(),
                TextColumn::make('duration_seconds')
                    ->label('Detik')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
