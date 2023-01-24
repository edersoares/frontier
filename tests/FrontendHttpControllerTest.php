<?php

namespace Dex\Laravel\Frontier\Tests;

use Illuminate\Support\Facades\Http;

class FrontendHttpControllerTest extends TestCase
{
    protected string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = storage_path('frontier');

        exec('mkdir -p ' . $this->storagePath);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        exec('rm -R ' . $this->storagePath);
    }

    public function testHttpController(): void
    {
        $file = storage_path("framework/views/frontier-http.html");
        $text = 'Frontier by HTTP';

        Http::fake([
            'localhost' => Http::response($text),
        ]);

        $this->get('/http')
            ->assertStatus(200)
            ->assertSeeText($text);

        $this->assertFileExists($file);
        $this->assertStringEqualsFile($file, $text);

        $this->get('/http')
            ->assertStatus(200)
            ->assertSeeText($text);
    }
}
