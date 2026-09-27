<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HedgeDocSourceTest extends TestCase
{
    public function test_notes_use_the_configured_source_and_rewrite_its_links(): void
    {
        config(['services.hedgedoc.url' => 'https://notes.example.test/wiki/']);
        Http::preventStrayRequests();
        Http::fake([
            'https://notes.example.test/wiki/example/download' => Http::response(
                "# Example\n\n[Local](/s/Local123)\n\n[Absolute](https://notes.example.test/wiki/s/Linked123?x=1#section)\n\n[Other](https://other.example.test/s/Other123)"
            ),
        ]);

        $response = $this->get('/profundarium/example');
        $response->assertOk();
        $response->assertSee('href="/profundarium/Local123"', false);
        $response->assertSee('href="/profundarium/Linked123?x=1#section"', false);
        $response->assertSee('href="https://other.example.test/s/Other123"', false);
        Http::assertSent(fn ($request) => $request->url() === 'https://notes.example.test/wiki/example/download');
    }
}
