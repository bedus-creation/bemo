<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);

        $results = Ticket::query()
            ->with('category')
            ->when($status = $request->input('status'), function (Builder $query) use ($status) {
                $query->where('status', $status);
            })->when($category = $request->input('category'), function (Builder $query) use ($category) {
                $query->where('category', $category);
            })->when($q = $request->input('query'), function (Builder $query) use ($q) {
                $query->where('subject', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%");
            })->orderByDesc('created_at')
            ->paginate($perPage);

        return TicketResource::collection($results);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::query()->create($request->validated());

        // TODO: Should we dispatch the job after the create?
        // TicketClassifierJob::dispatch($ticket->id)->afterCommit();

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $ticket->load(['category', 'classification', 'classification.category']);

        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket->update($request->validated());

        // TODO: Should we dispatch the job after the update?
        // TicketClassifierJob::dispatch($ticket->id)->afterCommit();

        return new TicketResource($ticket);
    }
}
