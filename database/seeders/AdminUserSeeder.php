<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed default admin user for the application.
     */
    public function run(): void
    {
        $this->command->info('Creating admin user...');

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@gapura.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@gapura.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('✅ Admin user created successfully!');
            $this->command->line('');
            $this->command->line('═══════════════════════════════════════');
            $this->command->line('  LOGIN CREDENTIALS');
            $this->command->line('═══════════════════════════════════════');
            $this->command->line('  Email    : admin@gapura.com');
            $this->command->line('  Password : password');
            $this->command->line('═══════════════════════════════════════');
            $this->command->line('');
            $this->command->warn('⚠️  IMPORTANT: Change this password in production!');
        } else {
            $this->command->info('ℹ️  Admin user already exists.');
        }
    }
}
