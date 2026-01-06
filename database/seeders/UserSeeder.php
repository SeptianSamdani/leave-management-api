<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\LeaveQuota;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Employee user
        $employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'email_verified_at' => now(),
        ]);

        // Create leave quota for employee
        // LeaveQuota::create([
        //     'user_id' => $employee->id,
        //     'year' => now()->year,
        //     'total' => 12,
        //     'used' => 0,
        //     'remaining' => 12,
        // ]);
    }
}