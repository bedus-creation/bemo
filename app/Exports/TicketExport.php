<?php

namespace App\Exports;

use App\Dtos\TicketFilterDto;
use App\Models\Ticket;
use App\Queries\TicketListQuery;

class TicketExport
{
    public function headers(): array
    {
        return [
            'id' => 'Id',
            'subject' => 'Subject',
            'body' => 'Body',
            'status' => 'Status',
            'note' => 'Note',
            'category' => 'Category',
            'ai_category' => 'AI Category',
            'ai_explanation' => 'AI Explanation',
            'ai_confidence' => 'AI Confidence',
            'created at' => 'Created Date',
            'updated at' => 'Updated Date',
        ];
    }

    public function inertRows($file, TicketFilterDto $dto): void
    {
        (new TicketListQuery($dto))
            ->getQuery()
            ->chunkById(100, function ($records) use ($file) {
                foreach ($records as $record) {
                    /** @var Ticket $record */
                    $ticket = [
                        'id' => $record->id,
                        'subject' => $record->subject,
                        'body' => $record->body,
                        'status' => $record->status->value,
                        'note' => $record->note,
                        'category' => $record->category->name ?? null,
                        'ai_category' => $record->classification->category->name ?? null,
                        'ai_explanation' => $record->classification->explanation ?? null,
                        'ai_confidence' => $record->classification->confidence ?? null,
                        'created at' => $record->created_at->format('Y-m-d H:i:s'),
                        'updated at' => $record->updated_at->format('Y-m-d H:i:s'),
                    ];

                    fputcsv($file, $ticket);
                }
            });
    }

    public function handle(TicketFilterDto $dto): void
    {
        $file = fopen('php://output', 'w');

        fputcsv($file, $this->headers());

        $this->inertRows($file, $dto);

        fclose($file);
    }
}
