<?php

namespace Dex\Laravel\Frontier\Tests;

use Illuminate\Support\Facades\Http;

class FrontierTest extends TestCase
{
    public function testHttpController()
    {
        Http::fake([
            'localhost' => Http::response('Frontier by HTTP'),
        ]);

        $this->get('/http')
            ->assertStatus(200)
            ->assertSeeText('Frontier by HTTP');
    }

    public function testViewController()
    {
        $this->get('/view')
            ->assertStatus(200)
            ->assertSeeText('Frontier by View');
    }
}
