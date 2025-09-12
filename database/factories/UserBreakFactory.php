<?php

namespace Database\Factories;

use App\Models\UserBreak;
use App\Models\UserDailyShift;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserBreak>
 */
class UserBreakFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserBreak::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $breakStart = $this->faker->dateTimeBetween('-8 hours', '-1 hour');
        $breakEnd = $this->faker->dateTimeBetween($breakStart, 'now');
        $breakDuration = Carbon::parse($breakStart)->diffInMinutes(Carbon::parse($breakEnd));

        return [
            'user_daily_shift_id' => UserDailyShift::factory(),
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'break_duration_mins' => $breakDuration,
            'break_type' => $this->faker->randomElement(['lunch', 'coffee', 'personal', 'other']),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the break is currently active (not ended).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'break_start' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'break_end' => null,
            'break_duration_mins' => null,
        ]);
    }

    /**
     * Indicate that the break is a lunch break.
     */
    public function lunch(): static
    {
        return $this->state(fn (array $attributes) => [
            'break_type' => 'lunch',
            'break_duration_mins' => $this->faker->numberBetween(30, 60), // Lunch breaks are typically longer
        ]);
    }

    /**
     * Indicate that the break is a coffee break.
     */
    public function coffee(): static
    {
        return $this->state(fn (array $attributes) => [
            'break_type' => 'coffee',
            'break_duration_mins' => $this->faker->numberBetween(5, 15), // Coffee breaks are typically shorter
        ]);
    }

    /**
     * Indicate that the break is a personal break.
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'break_type' => 'personal',
            'break_duration_mins' => $this->faker->numberBetween(10, 30),
        ]);
    }

    /**
     * Create a break with specific duration.
     */
    public function withDuration(int $minutes): static
    {
        $breakStart = $this->faker->dateTimeBetween('-8 hours', '-1 hour');
        $breakEnd = Carbon::parse($breakStart)->addMinutes($minutes);

        return $this->state(fn (array $attributes) => [
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'break_duration_mins' => $minutes,
        ]);
    }
}