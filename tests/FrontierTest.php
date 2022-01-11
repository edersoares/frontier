<?php

namespace Dex\Laravel\Frontier\Tests;

class FrontierTest extends TestCase
{
    public function testFrontierRoute()
    {
        $this->get('/frontier')
            ->assertStatus(200)
            ->assertSeeText('Frontier');
    }
}
