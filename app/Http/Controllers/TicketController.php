<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Jobs\ClassifyTicket;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    // GET /tickets
    public function index(Request $request)
    {
        $query = Ticket::query();

        // Filters: status, category
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Search: q in subject/body
        if ($q = $request->query('q')) {
            $query->where(function ($qbuilder) use ($q) {
                $qbuilder->where('subject', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%");
            });
        }

        // Pagination params
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->orderByDesc('created_at')->paginate($perPage)->appends($request->query());

        return TicketResource::collection($paginator);
    }

    // GET /tickets/stats
    public function stats(): JsonResponse
    {
        $total = Ticket::query()->count();

        // Status counts
        $statusRows = Ticket::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $status = [
            'open' => (int) ($statusRows['open'] ?? 0),
            'pending' => (int) ($statusRows['pending'] ?? 0),
            'closed' => (int) ($statusRows['closed'] ?? 0),
        ];

        // Category counts (dynamic keys)
        $category = Ticket::query()
            ->select('category', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn ($v) => (int) $v);

        return response()->json([
            'total' => (int) $total,
            'status' => $status,
            'categories' => $category,
        ]);
    }

    // POST /tickets
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create($request->validated());
        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    // GET /tickets/{ticket}
    public function show(Ticket $ticket): TicketResource
    {
        return new TicketResource($ticket);
    }

    // PATCH /tickets/{ticket}
    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket->fill($request->validated());
        $ticket->save();
        return new TicketResource($ticket);
    }

    // POST /tickets/{ticket}/classify
    public function classify(Ticket $ticket): JsonResponse
    {
        ClassifyTicket::dispatch($ticket);
        return response()->json([
            'message' => 'Classification job dispatched.'
        ], 202);
    }
}
