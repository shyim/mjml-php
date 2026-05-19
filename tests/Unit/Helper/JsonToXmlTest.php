<?php

declare(strict_types=1);

namespace Mjml\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Mjml\Helper\JsonToXml;
use Mjml\Parser\Node;

final class JsonToXmlTest extends TestCase
{
    public function testConvertNodeTreeToXml(): void
    {
        $tree = new Node(
            tagName: 'mjml',
            children: [
                new Node(
                    tagName: 'mj-body',
                    children: [
                        new Node(
                            tagName: 'mj-section',
                            children: [
                                new Node(
                                    tagName: 'mj-column',
                                    children: [
                                        new Node(
                                            tagName: 'mj-text',
                                            attributes: [
                                                'font-size' => '20px',
                                                'color' => '#F45E43',
                                                'font-family' => 'helvetica',
                                            ],
                                            content: 'Hello World',
                                        ),
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
            ],
        );

        $xml = JsonToXml::convert($tree);

        $expected = '<mjml><mj-body><mj-section><mj-column><mj-text font-size="20px" color="#F45E43" font-family="helvetica">Hello World</mj-text></mj-column></mj-section></mj-body></mjml>';

        self::assertSame($expected, $xml);
    }
}
