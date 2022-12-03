<?php

namespace Dex\Laravel\Frontier\Tests;

class FrontendViewControllerTest extends TestCase
{
    public function testViewController(): void
    {
        $this->get('/view')
            ->assertStatus(200)
            ->assertSeeText('Frontier by View');
    }
}
