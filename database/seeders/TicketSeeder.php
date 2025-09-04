<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        // Create a diverse set of tickets with Faker via the factory
        // The factory is configured to randomly include notes on some tickets.
        Ticket::factory()->count(30)->create();
    }
}
