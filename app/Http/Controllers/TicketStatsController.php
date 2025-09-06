<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
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

        // Category counts (dynamic keys)
        $category = Ticket::query()
            ->select('ticket_category_id', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('ticket_category_id')
            ->groupBy('ticket_category_id')
            ->pluck('aggregate', 'ticket_category_id')
            ->map(fn($v) => (int) $v);

        return response()->json([
            'total'      => (int) $total,
            'status'     => $status,
            'categories' => $category,
        ]);
    }
}
