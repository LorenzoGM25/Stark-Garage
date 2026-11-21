<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'pos.access',
            'pos.void',
            'products.manage',
            'employees.manage',
            'attendance.view',
            'attendance.manage',
            'attendance.face',
            'payroll.view',
            'payroll.generate',
            'payroll.pay',
            'reports.view',
            'expenses.manage',
            'settings.manage',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->givePermissionTo(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'dashboard.view', 'pos.access', 'products.manage', 'employees.manage',
            'attendance.view', 'attendance.manage', 'payroll.view', 'payroll.generate',
            'reports.view', 'expenses.manage',
        ]);

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->givePermissionTo(['dashboard.view', 'pos.access']);

        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->givePermissionTo(['attendance.view', 'attendance.face']);
    }
}
