<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('PLATFORM_ADMIN_EMAIL', 'admin@zuritours.test');
        $password = env('PLATFORM_ADMIN_PASSWORD', 'Password@123');
        $name = env('PLATFORM_ADMIN_NAME', 'Platform Super Admin');

        $admin = PlatformUser::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'status' => 'active',
            ]
        );

        $superAdminRole = Role::query()->firstOrCreate(
            [
                'company_id' => null,
                'guard_name' => 'platform',
                'name' => 'Super Admin',
            ],
            [
                'type' => 'platform',
            ]
        );

        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
    }
}
