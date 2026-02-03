<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppSetting::updateOrCreate(
            ['key' => 'ping_password'],
            ['value' => 'SECURE_V1']
        );

        AppSetting::updateOrCreate(
            ['key' => 'test_mode'],
            ['value' => '0']
        );

        AppSetting::updateOrCreate(
            ['key' => 'access_code_prefix'],
            ['value' => 'UQD']
        );
    }
}
