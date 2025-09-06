<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketCategoryResource;
use App\Models\TicketCategory;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketCategoryController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        $categories = TicketCategory::query()->orderBy('name')->get();

        return TIcketCategoryResource::collection($categories);
    }
}
