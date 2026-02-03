<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.settings-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'ping_password' => AppSetting::query()
                ->where('key', 'ping_password')
                ->value('value'),
            'test_mode' => AppSetting::query()
                ->where('key', 'test_mode')
                ->value('value') === '1',
            'access_code_prefix' => AppSetting::query()
                ->where('key', 'access_code_prefix')
                ->value('value') ?? 'UQD',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('ping_password')
                    ->label('Ping Password')
                    ->required()
                    ->maxLength(255),
                Toggle::make('test_mode')
                    ->label('Test Mode')
                    ->helperText('Aktifkan alur ujian via browser untuk testing.')
                    ->default(false),
                TextInput::make('access_code_prefix')
                    ->label('Access Code Prefix')
                    ->helperText('Prefix kode akses siswa, contoh: UQD')
                    ->required()
                    ->maxLength(6),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AppSetting::updateOrCreate(
            ['key' => 'ping_password'],
            ['value' => $data['ping_password']]
        );

        AppSetting::updateOrCreate(
            ['key' => 'test_mode'],
            ['value' => ! empty($data['test_mode']) ? '1' : '0']
        );

        AppSetting::updateOrCreate(
            ['key' => 'access_code_prefix'],
            ['value' => strtoupper(trim($data['access_code_prefix']))]
        );

        Notification::make()
            ->title('Settings diperbarui')
            ->success()
            ->send();
    }
}
