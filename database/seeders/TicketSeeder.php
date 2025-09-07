<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        Ticket::factory()->count(30)->create();

        $answer = select(
            label: 'Want to seed 1 millions tickets for export? (y/n)',
            options: ['y', 'n'],
            default: 'y'
        );

        if ($answer == 'y') {
            $this->seedForExport();
        }
    }

    public function seedForExport(): void
    {
        $batchSize = 1000;     // insert 1000 at a time
        $total = 1000000;  // 1 million tickets

        for ($i = 1; $i <= $total; $i += $batchSize) {
            $records = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $id = $i + $j;

                $records[] = [
                    'id' => strtolower((string) Str::ulid()),
                    'subject' => "Test Subject $id",
                    'body' => "This is the body of ticket $id",
                    'ticket_category_id' => rand(1, 10),             // random category
                    'status' => 'open',                  // example
                    'note' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('tickets')->insert($records);

            echo "Inserted $i - ".($i + $batchSize - 1)."\n";
        }
    }
}
