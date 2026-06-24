<?php

declare(strict_types=1);

namespace Softspring\CmsTranslationPlugin\Tests\Unit\Admin\ActionListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion\BlameListener as ContentBlameListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion\ExportListener as ContentExportListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion\ImportListener as ContentImportListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion\TranslationsListener as ContentTranslationsListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion\BlameListener as SectionBlameListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion\ExportListener as SectionExportListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion\ImportListener as SectionImportListener;
use Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion\TranslationsListener as SectionTranslationsListener;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;

class SubscribedEventsTest extends TestCase
{
    /**
     * @param class-string $listenerClass
     * @param array<string, list<array{0:string, 1:int}>> $expectedEvents
     */
    #[DataProvider('provideListenerEvents')]
    public function testListenersExposeTheirCrudlEventMap(string $listenerClass, array $expectedEvents): void
    {
        self::assertSame($expectedEvents, $listenerClass::getSubscribedEvents());
    }

    /**
     * @return iterable<string, array{0:class-string, 1:array<string, list<array{0:string, 1:int}>>}>
     */
    public static function provideListenerEvents(): iterable
    {
        yield 'content translations' => [
            ContentTranslationsListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_INITIALIZE => [
                    ['onInitializeGetConfig', 20],
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onEventLoadContentEntity', 9],
                    ['onInitializeUpdateHelperConfig', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_ENTITY => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onTranslationsLoadEntity', 1],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_PREPARE => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onFormPrepareResolve', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_INIT => [
                    ['onEventDispatchContentTypeEvent', 10],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_VALID => [
                    ['onEventDispatchContentTypeEvent', 10],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_APPLY => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onApply', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_SUCCESS => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onSuccess', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FAILURE => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onFailureShowAlert', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_FORM_INVALID => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onFormInvalidShowAlert', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_VIEW => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onView', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXCEPTION => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onException', 0],
                ],
            ],
        ];

        yield 'content export' => [
            ContentExportListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_INITIALIZE => [
                    ['onInitializeGetConfig', 20],
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onEventLoadContentEntity', 9],
                    ['onInitializeUpdateHelperConfig', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_ENTITY => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onEntityLoadVersionEntity', 1],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_NOT_FOUND => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onNotFound', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_FOUND => [
                    ['onEventDispatchContentTypeEvent', 10],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_APPLY => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onApply', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_SUCCESS => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onSuccess', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_FAILURE => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onFailure', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_EXPORT_EXCEPTION => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onException', 0],
                ],
            ],
        ];

        yield 'content import' => [
            ContentImportListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_INITIALIZE => [
                    ['onInitializeGetConfig', 20],
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onEventLoadContentEntity', 9],
                    ['onInitializeUpdateHelperConfig', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_ENTITY => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onCreateEntity', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_FORM_PREPARE => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onFormPrepareResolve', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_FORM_INIT => [
                    ['onEventDispatchContentTypeEvent', 10],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_FORM_VALID => [
                    ['onEventDispatchContentTypeEvent', 10],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_APPLY => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onApply', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_SUCCESS => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onSuccess', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_FAILURE => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onFailure', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_FORM_INVALID => [
                    ['onEventDispatchContentTypeEvent', 10],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_VIEW => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onView', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_IMPORT_EXCEPTION => [
                    ['onEventDispatchContentTypeEvent', 10],
                    ['onException', 0],
                ],
            ],
        ];

        yield 'section translations' => [
            SectionTranslationsListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_INITIALIZE => [
                    ['onLoadSectionEntity', 9],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_ENTITY => [
                    ['onTranslationsLoadEntity', 1],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_FORM_PREPARE => [
                    ['onFormPrepareResolve', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_APPLY => [
                    ['onApply', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_SUCCESS => [
                    ['onSuccess', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_FAILURE => [
                    ['onFailureShowAlert', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_FORM_INVALID => [
                    ['onFormInvalidShowAlert', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_VIEW => [
                    ['onViewAddEntities', 0],
                    ['onViewAddVariables', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXCEPTION => [
                    ['onException', 0],
                ],
            ],
        ];

        yield 'section export' => [
            SectionExportListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_INITIALIZE => [
                    ['onLoadSectionEntity', 9],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_ENTITY => [
                    ['onEntityLoadVersionEntity', 1],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_APPLY => [
                    ['onApply', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_SUCCESS => [
                    ['onSuccess', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_FAILURE => [
                    ['onFailure', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_EXCEPTION => [
                    ['onException', 0],
                ],
            ],
        ];

        yield 'section import' => [
            SectionImportListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_INITIALIZE => [
                    ['onLoadSectionEntity', 9],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_ENTITY => [
                    ['onCreateEntity', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FORM_PREPARE => [
                    ['onFormPrepareResolve', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_APPLY => [
                    ['onApply', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_SUCCESS => [
                    ['onSuccess', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FAILURE => [
                    ['onFailure', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_VIEW => [
                    ['onViewAddEntities', 0],
                    ['onViewAddPrevVersion', 0],
                ],
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_EXCEPTION => [
                    ['onException', 0],
                ],
            ],
        ];

        yield 'content blame' => [
            ContentBlameListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_CONTENT_VERSIONS_TRANSLATIONS_APPLY => [
                    ['onCreateVersion', 5],
                ],
            ],
        ];

        yield 'section blame' => [
            SectionBlameListener::class,
            [
                SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_APPLY => [
                    ['onCreateVersion', 5],
                ],
            ],
        ];
    }
}
