<?php

namespace Feature\Http;

use Tests\TestCase;

class TicketCategoryControllerTest extends TestCase
{
    public function test_it_returns_all_the_categories()
    {
        $this->getJson(route('api.categories.index'))
            ->assertOk();
    }
}
