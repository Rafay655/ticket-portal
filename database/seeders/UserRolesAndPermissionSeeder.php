<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRolesAndPermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Index	View	Create	Edit	Delete        
            // User Management Permissions
            'user.view',
            'user.index',
            'user.create',
            'user.edit',
            'user.delete',

            // Employee Management Permissions
            'employee.view',
            'employee.index',
            'employee.create',
            'employee.edit',
            'employee.delete',
            
            // Role and Permission Management
            'role.view',
            'role.index',
            'role.create',
            'role.edit',
            'role.delete',
            
            // Ticket Permissions
            'ticket.view',
            'ticket.index',
            'ticket.create',
            'ticket.edit',
            'ticket.delete',
            
            // Report Permissions
            'report.index',

            // Businesshours Permissions
            'businesshours.view',
            'businesshours.index',
            'businesshours.create',
            'businesshours.edit',
            'businesshours.delete',

            // SlaPolicies Permissions
            'slapolicies.index',
            'slapolicies.create',
            'slapolicies.edit',
            'slapolicies.delete',
            'slapolicies.view',

            // TicketType Permissions
            'ticketType.index',
            'ticketType.create',
            'ticketType.edit',
            'ticketType.delete',
            'ticketType.view',

            // Ticket SubType Permissions
            'ticketSubType.index',
            'ticketSubType.create',
            'ticketSubType.edit',
            'ticketSubType.delete',
            'ticketSubType.view',

            // Ticket Settings Permissions
            'ticketSettings.index',
            'ticketSettings.create',
            'ticketSettings.edit',
            'ticketSettings.delete',
            'ticketSettings.view',
           
            // Designation Permissions
            'designation.index',
            'designation.create',
            'designation.edit',
            'designation.delete',
            'designation.view',
           
            // Department Permissions
            'department.index',
            'department.create',
            'department.edit',
            'department.delete',
            'department.view',

            // Region Permissions
            'region.index',
            'region.create',
            'region.edit',
            'region.delete',
            'region.view',

            // Grade Permissions
            'grade.index',
            'grade.create',
            'grade.edit',
            'grade.delete',
            'grade.view',

        ];

        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 1. Super Admin - Assign all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'superAdmin']);
        $superAdmin->givePermissionTo(Permission::all()); // Give all permissions

     
    }
}
