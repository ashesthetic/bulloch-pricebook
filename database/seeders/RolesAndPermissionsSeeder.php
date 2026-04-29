<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    const MODULES = [
        'departments',
        'skus',
        'price_groups',
        'deal_groups',
        'mix_and_matches',
        'loyalty_cards',
        'tender_coupons',
        'payouts',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::MODULES as $module) {
            Permission::firstOrCreate(['name' => "view_{$module}"]);
            Permission::firstOrCreate(['name' => "create_{$module}"]);
            Permission::firstOrCreate(['name' => "edit_{$module}"]);
        }

        Permission::firstOrCreate(['name' => 'view_find_products']);

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'staff']);

        // Seed a default admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('111111')]
        );
        $admin->syncRoles(['admin']);
    }
}
