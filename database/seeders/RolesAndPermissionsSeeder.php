<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'sellers.approve',
            'sellers.block',
            'plans.manage',
            'reports.view',
            'reports.accounts.view',
            'banners.manage',
            'reviews.view',
            'menu.manage',
            'orders.manage.own',
            'subscriptions.view',
            'subscriptions.pay',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $sellerOwner = Role::firstOrCreate(['name' => 'seller_owner', 'guard_name' => 'sanctum']);
        $vendor = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'sanctum']);
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'sanctum']);

        $superAdmin->syncPermissions($permissions);

        $sellerOwner->syncPermissions([
            'menu.manage',
            'orders.manage.own',
            'reports.accounts.view',
            'subscriptions.view',
            'subscriptions.pay',
        ]);

        $vendor->syncPermissions([
            'menu.manage',
            'orders.manage.own',
            'reports.accounts.view',
        ]);

        $customer->syncPermissions([]);
    }
}
