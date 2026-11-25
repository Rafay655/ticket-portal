<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TicketCategoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actions = ['index', 'create', 'edit', 'delete', 'view'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => 'ticketCategory.'.$action, 'guard_name' => 'web']);
        }
        foreach (Role::where('guard_name', 'web')->with('permissions')->get() as $role) {
            $existing = $role->permissions->pluck('name')->all();
            foreach ($actions as $action) {
                if ($role->name === 'superAdmin' || in_array('admindashboard.index', $existing, true) || in_array('ticketType.'.$action, $existing, true)) {
                    $role->givePermissionTo('ticketCategory.'.$action);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
