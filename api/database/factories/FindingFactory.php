<?php

namespace Database\Factories;

use App\Models\Finding;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Finding> */
class FindingFactory extends Factory
{
    protected $model = Finding::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'check' => fake()->randomElement(['missing_title', 'missing_meta_desc', 'missing_h1', 'slow_ttfb', 'no_https']),
            'severity' => fake()->randomElement(['high', 'medium', 'low']),
            'status' => 'open',
            'message' => fake()->sentence(),
        ];
    }
}

