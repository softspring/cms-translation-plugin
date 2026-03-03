<?php

namespace Softspring\CmsTranslationPlugin\Tests\Exchange;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Softspring\CmsTranslationPlugin\Exchange\ExportException;
use Softspring\CmsTranslationPlugin\Exchange\Xliff12Exchanger;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\HttpFoundation\File\File;

class Xliff12ExchangerTest extends TestCase
{
    public function testFormat(): void
    {
        $this->assertEquals('xliff12', Xliff12Exchanger::format());
    }

    /**
     * @throws ExportException
     */
    public function testEmptyExport(): void
    {
        $exchanger = new Xliff12Exchanger();

        $file = $exchanger->exportFile([], 'test_domain', 'en', 'es');

        $this->assertEquals('test_domain.en.xlf', $file->getFilename());
        $xliffContent = $file->getContent();

        XmlUtils::loadFile($file->getPathname());

        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-8"?>', $xliffContent);
        $this->assertStringContainsString('<file source-language="es" target-language="en" datatype="plaintext" original="test_domain">', $xliffContent);

        unlink($file->getPathname());
    }

    /**
     * @throws ExportException
     */
    public function testExport(): void
    {
        $exchanger = new Xliff12Exchanger();

        $translations = [
            'test_key1' => [
                'es' => 'Prueba',
                'en' => 'Test with <b>html</b>',
            ],
            'test_key2' => [
                'es' => 'Prueba con trans id',
                'en' => 'Test with trans id',
                '_trans_id' => 'hash2',
                '_metadata' => [
                    'en' => [
                        'meta1' => 'meta value 1',
                    ],
                ],
                '_notes' => [
                    [
                        'priority' => 1,
                        'from' => 'John Doe',
                        'content' => 'note 1',
                    ],
                ],
            ],
            'test_key1:_module' => 'to be ignored',
        ];

        $options = [
            'exportFileNameBase' => 'testExport',
            'ref' => [
                'class' => 'App\TestClass',
                'id' => 'test1',
                'version' => 'v1',
            ],
        ];

        $file = $exchanger->exportFile($translations, 'test_domain', 'en', 'es', $options);

        $this->assertTrue(file_exists($file->getPathname()));

        $this->assertEquals('testExport.en.xlf', $file->getFilename());

        $xliffXmlContent = $file->getContent();
        $xliffXml = XmlUtils::loadFile($file->getPathname());

        $fileNode = $xliffXml->getElementsByTagName('file')->item(0);
        $headerNode = $fileNode->getElementsByTagName('header')->item(0);
        $bodyNode = $fileNode->getElementsByTagName('body')->item(0);
        $transUnitNodes = $bodyNode->getElementsByTagName('trans-unit');

        $this->assertEquals('es', $fileNode->getAttribute('source-language'));
        $this->assertEquals('en', $fileNode->getAttribute('target-language'));
        $this->assertEquals('plaintext', $fileNode->getAttribute('datatype'));
        $this->assertEquals('test_domain', $fileNode->getAttribute('original'));

        $refNode = $headerNode->getElementsByTagName('reference')->item(0);
        $this->assertEquals('App\TestClass', $refNode->getAttribute('class'));
        $this->assertEquals('test1', $refNode->getAttribute('id'));
        $this->assertEquals('v1', $refNode->getAttribute('version'));

        $this->assertEquals(2, $transUnitNodes->length);

        $transUnit1 = $transUnitNodes->item(0);
        $this->assertEquals('test1.test_key1', $transUnit1->getAttribute('id'));
        $this->assertEquals('test_key1', $transUnit1->getAttribute('resname'));
        $this->assertEquals('Prueba', $transUnit1->getElementsByTagName('source')->item(0)->nodeValue);
        $this->assertEquals('Test with <b>html</b>', $transUnit1->getElementsByTagName('target')->item(0)->nodeValue);
        $this->assertStringContainsString('<target><![CDATA[Test with <b>html</b>]]></target>', $xliffXmlContent);

        $transUnit2 = $transUnitNodes->item(1);
        $this->assertEquals('hash2', $transUnit2->getAttribute('id'));
        $this->assertEquals('test_key2', $transUnit2->getAttribute('resname'));
        $this->assertEquals('Prueba con trans id', $transUnit2->getElementsByTagName('source')->item(0)->nodeValue);
        $this->assertEquals('Test with trans id', $transUnit2->getElementsByTagName('target')->item(0)->nodeValue);
        $this->assertEquals('meta value 1', $transUnit2->getElementsByTagName('target')->item(0)->getAttribute('meta1'));
        $noteNode = $transUnit2->getElementsByTagName('note')->item(0);
        $this->assertEquals('note 1', $noteNode->nodeValue);
        $this->assertEquals('1', $noteNode->getAttribute('priority'));
        $this->assertEquals('John Doe', $noteNode->getAttribute('from'));

        unlink($file->getPathname());
    }

    public static function importTestData(): array
    {
        $test1 = [
            'xml' => <<<TEST1
<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file original="entityHashId_v58" xml:space="preserve" source-language="en" target-language="es" datatype="plaintext">
        <header>
            <tool tool-id="sfs-cms-translation-plugin" tool-name="SfsCms Translation Plugin" tool-version="5.3.9999999.9999999-dev"/>
            <reference class="Softspring\CmsBundle\Entity\Page" id="entityHashId" version="58"/>
        </header>
        <body>
            <trans-unit id="id1" resname="block[0]:title">
                <source>Page title</source>
                <target>Título de página cambiado</target>
                <note from="meaning">Page title</note>
            </trans-unit>
            <trans-unit id="id3" resname="block[2]:title">
                <source>Deleted translation</source>
                <target>Traducción eliminada</target>
            </trans-unit>
        </body>
    </file>
</xliff>
TEST1,
            'targetLocale' => 'es',
            'fallbackLocale' => 'en',
            'originalFlattenTranslations' => [
                'block[0]:title' => [
                    'en' => 'Page title',
                    'es' => 'Título de página',
                    '_trans_id' => 'id1',
                ],
                'block[1]:title' => [
                    'en' => 'Other title not included in xml',
                    'es' => 'Otro título de página no incluido en el xml',
                    '_trans_id' => 'id2',
                ],
            ],
            'expectedFlattenTranslations' => [
                'block[0]:title' => [
                    'en' => 'Page title',
                    'es' => 'Título de página cambiado',
                    '_trans_id' => 'id1',
                ],
                'block[1]:title' => [
                    'en' => 'Other title not included in xml',
                    'es' => 'Otro título de página no incluido en el xml',
                    '_trans_id' => 'id2',
                ],
            ],
            'expectedDomain' => 'entityHashId_v58',
            'expectedVersionNumber' => '58',
            'expectedEntityId' => 'entityHashId',
            'expectedEntityClass' => 'Softspring\CmsBundle\Entity\Page',
            'warnings' => [
                'Translation not applicable for id3, maybe module has been deleted',
            ],
        ];

        return [
            $test1,
        ];
    }

    #[DataProvider('importTestData')]
    public function testImport(
        string $xml,
        string $targetLocale,
        string $fallbackLocale,
        array $originalFlattenTranslations,
        array $expectedFlattenTranslations,
        string $expectedDomain,
        string $expectedVersionNumber,
        string $expectedEntityId,
        string $expectedEntityClass,
        array $warnings = [],
        array $changeLog = [],
    ): void
    {
        $exchanger = new Xliff12Exchanger();

        // create test xml file
        $file = new File(tempnam(sys_get_temp_dir(), 'test_import'), false);
        file_put_contents($file->getPathname(), $xml);

        $resultCollection = $exchanger->importFile($file, $originalFlattenTranslations);

        $this->assertEquals(1, count($resultCollection->getResults()));
        $result = $resultCollection->getResults()[0];

        // assert identifications
        $this->assertEquals($expectedDomain, $result->getDomain());
        $this->assertEquals($expectedVersionNumber, $result->getVersionNumber());
        $this->assertEquals($expectedEntityId, $result->getEntityId());
        $this->assertEquals($expectedEntityClass, $result->getEntityClass());

        // assert translations
        $this->assertEquals($originalFlattenTranslations, $result->getOriginalFlattenTranslations());
        $this->assertEquals($targetLocale, $result->getTargetLanguage());
        $this->assertEquals($fallbackLocale, $result->getSourceLanguage());
        $this->assertEquals($expectedFlattenTranslations, $result->getFlattenTranslations());

        // assert other data
        $this->assertTrue($result->hasGlobalWarnings());
        $this->assertEquals($warnings, $result->getGlobalWarnings());
        $this->assertEquals($changeLog, $result->getChangeLog());

        // remove test file
        unlink($file->getPathname());
    }
}
