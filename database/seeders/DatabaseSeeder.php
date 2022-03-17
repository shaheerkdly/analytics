<?php

namespace Database\Seeders;

use App\Models\Analytic;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $user_count = User::count();
        $status = false;
        if (!$user_count) {
            \App\Models\User::factory(10)->create();
            $status = true;
        }
        if ($status) {
            \App\Models\Analytic::factory(5000)->create();
        }
    }
}
