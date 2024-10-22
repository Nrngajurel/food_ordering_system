<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'isAdmin' => true,
            'password' => bcrypt('password'),
            'address' => 'Admin Address',
        ]);
        $this->call([
            UserTableSeeder::class,
            FoodTableSeeder::class,
            OrderTableSeeder::class,
            FoodOrderTableSeeder::class,
        ]);
    }
}
