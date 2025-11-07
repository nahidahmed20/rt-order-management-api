<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'Nahid',
            'email' => 'nahid@gamil.com',
            'password' => bcrypt('12345678'),
        ]);

        $this->call([
            ProductSeeder::class,
        ]);
    }
}
