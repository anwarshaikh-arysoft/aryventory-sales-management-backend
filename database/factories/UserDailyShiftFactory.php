<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDailyShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserDailyShift>
 */
class UserDailyShiftFactory extends Factory
{
    protected $model = UserDailyShift::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shift_date' => fake()->date(),
            'shift_start' => fake()->dateTime(),
            'shift_end' => null,
            'break_start' => null,
            'break_end' => null,
            'total_break_mins' => 0,
            'notes' => fake()->optional()->sentence(),
            'shift_start_selfie_image' => null,
            'shift_end_selfie_image' => null,
            'shift_start_latitude' => fake()->optional()->latitude(),
            'shift_start_longitude' => fake()->optional()->longitude(),
            'shift_end_latitude' => null,
            'shift_end_longitude' => null,
        ];
    }

    /**
     * Indicate that the shift is currently active (started but not ended).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start' => now()->subHours(2),
            'shift_end' => null,
        ]);
    }

    /**
     * Indicate that the shift is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start' => now()->subHours(8),
            'shift_end' => now()->subHours(1),
        ]);
    }

    /**
     * Indicate that the shift has selfie images.
     */
    public function withSelfies(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift_start_selfie_image' => 'selfies/2024/01/01/' . fake()->uuid() . '/shift_start_' . fake()->uuid() . '.jpg',
            'shift_end_selfie_image' => 'selfies/2024/01/01/' . fake()->uuid() . '/shift_end_' . fake()->uuid() . '.jpg',
        ]);
    }
}
