<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dtos\TicketFilterDto;
use App\Http\Requests\TicketListRequest;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Requests\TicketUpdateRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Queries\TicketListQuery;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function index(TicketListRequest $request, TicketListQuery $query)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $status = $request->input('status');
        $q = $request->input('query');
        $category = (int) $request->input('category');

        $ticketFilterDto = new TicketFilterDto(
            status: $status,
            category: $category,
            q: $q
        );

        $results = (new TicketListQuery($ticketFilterDto))
            ->getQuery()
            ->paginate(perPage: $perPage, page: $page);

        return TicketResource::collection($results);
    }

    public function store(TicketStoreRequest $request): JsonResponse
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

    public function update(TicketUpdateRequest $request, Ticket $ticket): TicketResource
    {
        $ticket->update($request->validated());

        // TODO: Should we dispatch the job after the update?
        // TicketClassifierJob::dispatch($ticket->id)->afterCommit();

        return new TicketResource($ticket);
    }
}
