<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SizedImagesTest extends TestCase
{
    public function test_sized_images_preserve_alt_text_and_dimensions(): void
    {
        config(['services.hedgedoc.url' => 'https://notes.example.test']);
        Http::fake(['https://notes.example.test/images/download' => Http::response(<<<'MD'
![MAGA kid turning and twisting bricks for his brick sorter](https://hedgedoc.beamtic.net/uploads/e704dd05-3a4f-4c4c-9443-1b4b8aa5d7d9.png =380x)

![Tall](https://example.test/tall.png =x240)

![Both](https://example.test/both.png =380x240)

![Pasted]\([https://example.test/pasted.png](https://example.test/pasted.png) =380x)

![Ordinary alt](https://example.test/plain.png)

![Æble "quoted" & text](https://example.test/escaped.png =40x)

![Square](https://example.test/square.png =256x256)

`![Inline code](https://example.test/code.png =380x)`

```markdown
![Fenced code](https://example.test/fence.png =380x)
```
MD, 200, ['Content-Type' => 'text/markdown'])]);

        $response = $this->get('/profundarium/images');
        $response->assertOk();
        $document = \Dom\HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR, 'UTF-8');
        $images = $document->getElementsByTagName('img');
        $this->assertCount(7, $images);
        $this->assertSame('MAGA kid turning and twisting bricks for his brick sorter', $images[0]->getAttribute('alt'));
        $this->assertSame('380', $images[0]->getAttribute('width'));
        $this->assertFalse($images[0]->hasAttribute('height'));
        $this->assertSame('240', $images[1]->getAttribute('height'));
        $this->assertFalse($images[1]->hasAttribute('width'));
        $this->assertSame('380', $images[2]->getAttribute('width'));
        $this->assertSame('240', $images[2]->getAttribute('height'));
        $this->assertSame('https://example.test/pasted.png', $images[3]->getAttribute('src'));
        $this->assertSame('Ordinary alt', $images[4]->getAttribute('alt'));
        $this->assertSame('Æble "quoted" & text', $images[5]->getAttribute('alt'));
        $this->assertSame('256', $images[6]->getAttribute('width'));
        $this->assertSame('256', $images[6]->getAttribute('height'));
        $response->assertSee('![Inline code](https://example.test/code.png =380x)', false);
        $response->assertSee('![Fenced code](https://example.test/fence.png =380x)', false);
    }
}
