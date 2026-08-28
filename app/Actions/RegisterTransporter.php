<?php

namespace App\Actions;

use App\Enums\TransporterStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RegisterTransporter
{
    /**
     * Register a transport company: creates the user account, grants the
     * transporter role and creates the company in "pending" status. The company
     * must be validated by an administrator before it can publish trips.
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            Role::findOrCreate(UserRole::Transporter->value, 'web');
            $user->assignRole(UserRole::Transporter->value);

            $user->transporter()->create([
                'company_name' => $data['company_name'],
                'phone' => $data['phone'] ?? null,
                'status' => TransporterStatus::Pending,
            ]);

            return $user;
        });
    }
}
