<?php

namespace Database\Factories;

use App\Models\Analytic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Analytic>
 */
class AnalyticFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $users = User::pluck('name', 'id')->toArray();
        return [
            'user_id' => array_rand($users),
            'date_time' => $this->faker->dateTimeInInterval('-5 month', '+5 month'),
            'country' => array_rand(config('countries')),
            'ip_address' => $this->faker->ipv4(),
            'referer' => array_rand(config('referers')),
        ];
    }
}
