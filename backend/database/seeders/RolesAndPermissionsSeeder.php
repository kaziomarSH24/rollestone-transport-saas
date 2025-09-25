<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // -----------------------------------------------------------------
        // 1. PERMISSION DEFINITIONS
        // Permissions are organized by module for better readability.
        // -----------------------------------------------------------------

        $permissionsByModule = [
            'Company Management' => [
                'create companies',
                'view companies',
                'edit companies',
                'delete companies',
            ],
            'Dashboard & Reports' => [
                'view dashboard',
                'view reports',
            ],
            'Driver Management' => [
                'create drivers',
                'view drivers',
                'edit drivers',
                'delete drivers',
            ],
            'Passenger Management' => [
                'create passengers',
                'view passengers',
                'edit passengers',
                'delete passengers',
                'manage passenger wallet', // For adding/deducting balance
            ],
            'Route & Fare Management' => [
                'create routes',
                'view routes',
                'edit routes',
                'delete routes',
                'manage fares',
            ],
            'Trip Management' => [
                'create trips',
                'view trips',
                'edit trips',
                'delete trips',
            ],
            'Driver App Permissions' => [
                'start own trip',
                'end own trip',
                'validate tickets', // For QR code scan
            ],
            'Transaction Management' => [
                'view transactions',
                'process refunds',
                'manage cash reconciliation',
            ],
            'Messaging & Notifications' => [
                'send notifications',
                'view notifications',
            ],
            'Support Ticket Management' => [
                'view support tickets',
                'reply support tickets',
            ],
            'System Access Control' => [
                'access admin dashboard',
                'access driver console',
                'access trip & route management',
                'access passenger database',
                'access fare control',
                'access notification system',
                'access analytics',
            ],
            'Settings Management' => [
                'manage settings',
                'manage faqs',
            ],

            'Message Management' => [
                'send messages',
                'view messages',
                'edit messages',
                'delete messages',
            ],
        ];

        // Create Permissions
        // NOTE: The 'web' guard is used because it is Laravel's default guard.
        // The default guard for API (Sanctum) authentication is also 'web'.
        // Using 'api' as the guard name will cause permission checks to fail unless the default guard is changed in config/auth.php.
        foreach ($permissionsByModule as $module => $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }

        // -----------------------------------------------------------------
        // 2. ROLE DEFINITIONS & PERMISSION ASSIGNMENTS
        // -----------------------------------------------------------------

        // SuperAdmin Role -> Can access everything.
        $superAdminRole = Role::findOrCreate('SuperAdmin', 'web');
        $superAdminRole->givePermissionTo(Permission::all());

        // CompanyAdmin Role -> Can do everything except manage companies.
        // Only company-related permissions are excluded from Permission::all().
        $companyAdminPermissions = Permission::where('name', '!=', 'create companies')
            ->where('name', '!=', 'view companies')
            ->where('name', '!=', 'edit companies')
            ->where('name', '!=', 'delete companies')
            ->get();
        $companyAdminRole = Role::findOrCreate('CompanyAdmin', 'web');
        $companyAdminRole->syncPermissions($companyAdminPermissions);


        // Driver Role -> Only has permissions required for the driver app.
        $driverRole = Role::findOrCreate('Driver', 'web');
        $driverRole->givePermissionTo([
            'start own trip',
            'end own trip',
            'validate tickets',
            'view notifications', // Drivers can view notifications.
            'manage passenger wallet',
            'view passengers',
            'view routes',
        ]);

        // Passenger Role -> No special backend permissions.
        // All their actions will be handled via dedicated API routes (e.g., /api/me/profile).
        $passengerRole = Role::findOrCreate('Passenger', 'web');
        $passengerRole->givePermissionTo([
            'view routes',
        ]);


        // -----------------------------------------------------------------
        // 3. (OPTIONAL) CREATE A DEFAULT SUPER ADMIN USER
        // Create a SuperAdmin for the initial system setup.
        // -----------------------------------------------------------------

        // First, create a default company for the SuperAdmin.
        $defaultCompany = \App\Models\Company::firstOrCreate(
            ['company_name' => 'Rolleston Express'],
            [
                'contact_email' => 'contact@rolleston.com',
                'subdomain' => 'rolleston',
                'status' => 'Active',
            ]
        );

        $superAdminUser = \App\Models\User::firstOrCreate(
            ['email' => 'superadmin@app.com'],
            [
                'company_id' => $defaultCompany->id,
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'address' => 'Main Office, Dhaka',
                'phone_number' => '1234567890',
                'email_verified_at' => now(),
                'password' => \Illuminate\Support\Facades\Hash::make('password'), // Change this later.
                'rider_type' => 'Adult',
                'status' => 'Active',
            ]
        );

        $superAdminUser->assignRole($superAdminRole);
    }

    // php artisan db:seed --class=RolesAndPermissionsSeeder
}
