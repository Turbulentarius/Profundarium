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

    public function test_relative_protocol_relative_and_direct_note_links(): void
    {
        config(['services.hedgedoc.url' => 'https://notes.example.test']);
        Http::preventStrayRequests();
        Http::fake(['https://notes.example.test/example/download' => Http::response(<<<'MD'
[Relative](s/Relative123#heading)
[Protocol relative](//notes.example.test/s/Protocol123)
[Direct](https://notes.example.test/abcdefghijklmnop?view=1#heading)
[External](https://other.example.test/abcdefghijklmnop)
[Fragment](#heading)
[Asset](https://notes.example.test/uploads/picture.png)
<a href="/s/Raw123">Raw HTML</a>
MD)]);

        $response = $this->get('/profundarium/example');
        $response->assertOk();
        foreach ([
            '/profundarium/Relative123#heading',
            '/profundarium/Protocol123',
            '/profundarium/abcdefghijklmnop?view=1#heading',
            'https://other.example.test/abcdefghijklmnop',
            '#heading',
            'https://notes.example.test/uploads/picture.png',
            '/profundarium/Raw123',
        ] as $href) {
            $response->assertSee('href="' . $href . '"', false);
        }
    }

}
