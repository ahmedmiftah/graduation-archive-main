<?php

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

test('user model has HasRoles trait', function () {
    expect(class_uses_recursive(User::class))->toHaveKey(HasRoles::class);
});

test('admin user has super_admin role', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed([RoleSeeder::class, AdminSeeder::class]);

    $admin = User::where('email', 'admin@admin.com')->first();

    expect($admin->hasRole('super_admin'))->toBeTrue();
});

test('user belongs to department relationship exists', function () {
    expect((new User())->department())->toBeInstanceOf(BelongsTo::class);
});
