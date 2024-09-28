<?php

namespace CTanner\LaravelDeepSync\Tests\Database\Factories;

use CTanner\LaravelDeepSync\Tests\Models\Subtask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class SubtaskFactory extends Factory
{
    protected $model = Subtask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
        ];
    }
}
