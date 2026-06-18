<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Translator;

use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Translator\InvalidTranslationMappingException;
use Softspring\CmsTranslationPlugin\Translator\TranslationsTransformer;

class TranslationsTransformerTest extends TestCase
{
    public function testItFlattensSeoAndNestedModules(): void
    {
        $translations = [
            '_seo' => [
                'title' => ['en' => 'SEO title'],
            ],
            'main' => [
                [
                    '_module' => 'text',
                    'title' => ['en' => 'Title'],
                    'modules' => [
                        [
                            '_module' => 'image',
                            'caption' => ['en' => 'Caption'],
                        ],
                    ],
                ],
            ],
        ];

        self::assertSame([
            '_seo:title' => ['en' => 'SEO title'],
            'main:0:_module' => 'text',
            'main:0:modules:0:_module' => 'image',
            'main:0:modules:0:caption' => ['en' => 'Caption'],
            'main:0:title' => ['en' => 'Title'],
        ], TranslationsTransformer::flatten($translations));
    }

    public function testItAppliesSeoTranslations(): void
    {
        self::assertSame([
            'title' => ['en' => 'Translated title'],
            'description' => ['en' => 'Description'],
        ], TranslationsTransformer::applySEO(
            ['title' => ['en' => 'Original title']],
            [
                '_seo:title' => ['en' => 'Translated title'],
                '_seo:description' => ['en' => 'Description'],
                'main:0:title' => ['en' => 'Ignored'],
            ],
        ));
    }

    public function testItAppliesFlattenTranslationsToExistingData(): void
    {
        $data = [
            'main' => [
                [
                    '_module' => 'text',
                    'title' => ['en' => 'Original'],
                ],
            ],
        ];

        self::assertSame([
            'main' => [
                [
                    '_module' => 'text',
                    'title' => ['en' => 'Translated'],
                ],
            ],
        ], TranslationsTransformer::applyFlatten($data, [
            'main:0:_module' => 'text',
            'main:0:title' => ['en' => 'Translated'],
            '_seo:title' => ['en' => 'Ignored'],
        ]));
    }

    public function testItReturnsNullWhenApplyingFlattenToEmptyData(): void
    {
        self::assertNull(TranslationsTransformer::applyFlatten(null, ['main:0:title' => ['en' => 'Title']]));
        self::assertNull(TranslationsTransformer::applyFlatten([], ['main:0:title' => ['en' => 'Title']]));
    }

    public function testItRejectsInvalidFlattenPath(): void
    {
        $this->expectException(InvalidTranslationMappingException::class);

        TranslationsTransformer::applyFlatten(['main' => []], ['main:1:title' => ['en' => 'Missing']]);
    }
}
