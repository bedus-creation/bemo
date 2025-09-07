<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(6),
            'body' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(['open', 'pending', 'closed']),
        ];
    }
}
