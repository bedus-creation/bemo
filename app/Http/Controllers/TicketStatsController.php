<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TicketStatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $total = Ticket::query()->count();

        // Status counts
        $statusRows = Ticket::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $status = [
            'open'    => (int) ($statusRows['open'] ?? 0),
            'pending' => (int) ($statusRows['pending'] ?? 0),
            'closed'  => (int) ($statusRows['closed'] ?? 0),
        ];

        $allStatuses = TicketStatus::cases();

        $categories = TicketCategory::query()->select('id', 'name')->get();

        $tickets = Ticket::query()->select('ticket_category_id', 'status', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('ticket_category_id')
            ->groupBy('ticket_category_id', 'status')
            ->get()
            ->groupBy('status');

        $datasets = collect($allStatuses)->map(function (TicketStatus $status) use ($categories, $tickets) {
            $data = $categories->map(function (TicketCategory $category) use ($status, $tickets) {
                $item = $tickets->get($status->value)?->firstWhere('ticket_category_id', $category->id);

                return $item ? $item->aggregate : 0;
            })->toArray();

            return [
                'label'           => ucfirst($status->value),
                'data'            => $data,
                'backgroundColor' => $status->color() ?? '#999',
            ];
        });

        return response()->json([
            'total'      => $total,
            'status'     => $status,
            'chartData' => [
                'labels'   => $categories->pluck('name'),
                'datasets' => $datasets,
            ],
        ]);
    }
}
