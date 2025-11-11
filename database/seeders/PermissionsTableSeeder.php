<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            ['name' => 'manage_users', 'label' => 'Manage Users'],
            ['name' => 'view_reports', 'label' => 'View Reports'],
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p['name']], ['label' => $p['label']]);
        }

        // แนบ permission ให้ role admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', array_column($perms, 'name'))->pluck('id')
            );
        }
    }
}
