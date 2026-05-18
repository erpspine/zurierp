<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class PlatformRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Support Admin',
            'Billing Admin',
            'Implementation Officer',
        ];

        foreach ($roles as $roleName) {
            Role::query()->updateOrCreate(
                [
                    'company_id' => null,
                    'guard_name' => 'platform',
                    'name' => $roleName,
                ],
                [
                    'type' => 'platform',
                ]
            );
        }
    }
}
