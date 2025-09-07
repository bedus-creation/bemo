<?php

namespace App\Http\Controllers;

use App\Dtos\TicketFilterDto;
use App\Exports\TicketExport;
use App\Http\Requests\TicketListRequest;
use Illuminate\Support\Facades\Response;

class TicketExportController extends Controller
{
    public function __invoke(TicketListRequest $request, TicketExport $export)
    {
        $status = $request->input('status');
        $q = $request->input('query');
        $category = $request->input('category');

        $ticketFilterDto = new TicketFilterDto(
            status: $status,
            category: $category,
            q: $q
        );

        $filename = sprintf('%s-tickets.csv', now()->format('Y-m-d h:i:s'));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return Response::stream(function () use ($export, $ticketFilterDto) {
            $export->handle($ticketFilterDto);
        }, 200, $headers);
    }
}
