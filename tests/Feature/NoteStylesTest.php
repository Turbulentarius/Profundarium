<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NoteStylesTest extends TestCase
{
    public function test_note_styles_survive_link_rewriting(): void
    {
        config(['services.hedgedoc.url' => 'http://hedgedoc:3000']);
        Http::fake([
            'http://hedgedoc:3000/style-check/download' => Http::response(<<<'MD'
<style>.note h1 { color: rgb(123, 45, 67); }</style>

# Styled note

[Another note](/s/AnotherNote?view=1#section)

<style media="screen">.note p { font-weight: 500; }</style>
MD),
        ]);

        $response = $this->get('/profundarium/style-check');

        $response->assertOk();
        $response->assertSee('<style>.note h1 { color: rgb(123, 45, 67); }</style>', false);
        $response->assertSee('<style media="screen">.note p { font-weight: 500; }</style>', false);
        $response->assertSee('href="/profundarium/AnotherNote?view=1#section"', false);
        $response->assertSee('id="styled-note"', false);
    }
}
