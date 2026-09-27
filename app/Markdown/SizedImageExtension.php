<?php

namespace App\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Util\UrlEncoder;

final class SizedImageExtension implements ExtensionInterface, InlineParserInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addInlineParser($this, 200);
    }

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::string('![');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        // Handle HedgeDoc dimensions and URLs pasted as Markdown links.
        // As an inline parser, this never rewrites fenced or inline code.
        $pattern = <<<'REGEX'
~^!\[(?<alt>(?:\\.|[^\]\\])*)\]\\?\(\s*(?:\[[^\]]*\]\((?<linked>[^\s()<>]+)\)|(?<url>[^\s()<>]+))\s+=(?<width>[1-9][0-9]*)?x(?<height>[1-9][0-9]*)?\s*\)~u
REGEX;
        $cursor = $inlineContext->getCursor();
        if (! preg_match($pattern, $cursor->getRemainder(), $match)) {
            return false;
        }
        $width = $match['width'] ?? '';
        $height = $match['height'] ?? '';
        if ($width === '' && $height === '') {
            return false;
        }

        $alt = preg_replace('/\\\\([[:punct:]])/', '$1', $match['alt']);
        $image = new Image(
            UrlEncoder::unescapeAndEncode($match['linked'] ?: $match['url']),
            html_entity_decode($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );
        $attributes = [];
        if ($width !== '') {
            $attributes['width'] = $width;
        }
        if ($height !== '') {
            $attributes['height'] = $height;
        }
        $image->data->set('attributes', $attributes);
        $cursor->advanceBy(mb_strlen($match[0], 'UTF-8'));
        $inlineContext->getContainer()->appendChild($image);

        return true;
    }
}
