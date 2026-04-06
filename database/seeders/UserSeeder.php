<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Default admin for local / staging. Run alone: php artisan db:seed --class=UserSeeder
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'divinejm@admin.com'],
            [
                'name' => 'Divine JM Admin',
                'password' => 'divinejmadmin',
                'email_verified_at' => now(),
            ]
        );
    }
}
