<?php

namespace Softspring\CmsTranslationPlugin\Exchange;

use Composer\InstalledVersions;
use DOMDocument;
use DOMText;
use ErrorException;
use Exception;
use Softspring\CmsTranslationPlugin\Utils\TranslationsCleaner;
use Softspring\TranslatableBundle\Model\Translation;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\HttpFoundation\File\File;

class Xliff12Exchanger implements ExchangerInterface
{
    public static function format(): string
    {
        return 'xliff12';
    }

    public static function supportsImport(File $file): bool
    {
        if ('text/xml' !== $file->getMimeType()) {
            return false;
        }

        try {
            self::readXmlFile($file);
        } catch (ImportException) {
            return false;
        }

        return true;
    }

    /**
     * @throws ImportException
     */
    protected static function readXmlFile(File $file): DOMDocument
    {
        $xml = XmlUtils::loadFile($file->getPathname());

        self::assertXliffXml($xml);

        return $xml;
    }

    /**
     * @throws ImportException
     */
    protected static function assertXliffXml(DOMDocument $xliff): void
    {
        if (1 !== $xliff->childElementCount) {
            throw new ImportException('Invalid XLIFF file');
        }

        $xliffNode = $xliff->firstElementChild;
        if ('xliff' !== $xliffNode->nodeName) {
            throw new ImportException('Invalid XLIFF file');
        }
        $xliffVersion = $xliffNode->getAttribute('version');
        $xliffNamespace = $xliffNode->getAttribute('xmlns');

        if ('1.2' !== $xliffVersion || 'urn:oasis:names:tc:xliff:document:1.2' !== $xliffNamespace) {
            throw new ImportException('Invalid XLIFF version or namespace');
        }
    }

    public function importFile(File $file, array $flattenTranslations): ImportResultCollection
    {
        // <?xml version="1.0" encoding="UTF-8"? >
        // <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
        //  <file original="1eda8479-2d77-68ca-b75d-a92d8d1d8eb0_v58" xml:space="preserve" source-language="es" target-language="en" datatype="plaintext">
        //    <header>
        //      <tool tool-id="sfs-cms-translation-plugin" tool-name="SfsCms Translation Plugin" tool-version="5.3.9999999.9999999-dev"/>
        //      <reference class="Softspring\CmsBundle\Entity\Page" id="1eda8479-2d77-68ca-b75d-a92d8d1d8eb0" version="58"/>
        //    </header>
        //    <body>
        //      <trans-unit id="66f1019ae8592" resname="_seo:metaTitle">
        //        <source>Page title</source>
        //        <target>Page title changed</target>
        //        <note from="meaning">Page title</note>
        //      </trans-unit>

        $xliffNode = $this->readXmlFile($file);

        $results = new ImportResultCollection();

        $fileNodes = $xliffNode->getElementsByTagName('file');

        if (1 !== $fileNodes->length) {
            throw new ImportException('Invalid XLIFF file, only one file node is allowed');
        }

        foreach ($fileNodes as $fileNode) {
            // TODO CHECK CMS PLUGIN VERSION
            // <header><tool tool-id="sfs-cms-translation-plugin" tool-name="SfsCms Translation Plugin" tool-version="5.3.9999999.9999999-dev"/>

            // get file header->reference data
            // <header><reference class="Softspring\CmsBundle\Entity\Page" id="1eda8479-2d77-68ca-b75d-a92d8d1d8eb0" version="58"/>
            $headerNode = $fileNode->getElementsByTagName('header')->item(0);
            $entityClass = $headerNode->getElementsByTagName('reference')->item(0)->getAttribute('class');
            $entityId = $headerNode->getElementsByTagName('reference')->item(0)->getAttribute('id');
            $versionNumber = $headerNode->getElementsByTagName('reference')->item(0)->getAttribute('version');

            $results->addResult($result = new ImportResult($flattenTranslations, $entityClass, $entityId, $versionNumber));
            $currentFlattenTranslations = $flattenTranslations;
            $result->setSourceLanguage($fileNode->getAttribute('source-language'));
            $result->setTargetLanguage($targetLanguage = $fileNode->getAttribute('target-language'));
            $result->setDomain($fileNode->getAttribute('original'));

            foreach ($fileNode->getElementsByTagName('body') as $bodyNode) {
                foreach ($bodyNode->getElementsByTagName('trans-unit') as $transUnitNode) {
                    $transId = $transUnitNode->getAttribute('id');
                    $resName = $transUnitNode->getAttribute('resname');
                    $module = $transUnitNode->getAttribute('data-module');
                    try {
                        $source = TranslationsCleaner::cleanText($transUnitNode->getElementsByTagName('source')->item(0)->textContent);
                    } catch (ErrorException $errorException) {
                        $source = '';
                    }
                    try {
                        $target = TranslationsCleaner::cleanText($transUnitNode->getElementsByTagName('target')->item(0)->textContent);
                    } catch (ErrorException $errorException) {
                        $target = '';
                    }

                    // TODO get notes

                    // apply translation by trans-id
                    $applied = false;
                    foreach ($currentFlattenTranslations as $key => $translation) {
                        if (!is_array($translation)) {
                            continue;
                        }

                        if (($translation['_trans_id'] ?? false) === $transId) {
                            // check module
                            $keyParts = explode(':', $key);
                            array_pop($keyParts);
                            $moduleKey = implode(':', $keyParts).':_module';
                            if ($module && ($currentFlattenTranslations[$moduleKey] ?? false) !== $module) {
                                $result->addGlobalWarning("Module not applicable for $transId, maybe module has been changed");
                                break;
                            }

                            $currentFlattenTranslations[$key][$targetLanguage] = $target;
                            $applied = true;
                            break;
                        }
                    }

                    // apply translation by other ways
                    if (!$applied) {
                        // try to apply by id directly
                        if (isset($currentFlattenTranslations[$transId])) {
                            $currentFlattenTranslations[$transId][$targetLanguage] = $target;
                        // try to apply by resname
                        } elseif ($resName && isset($currentFlattenTranslations[$resName])) {
                            $currentFlattenTranslations[$resName][$targetLanguage] = $target;
                            $result->addFieldWarning($resName, $targetLanguage, 'Translation applied, but this field field could have changed');
                        } else {
                            $result->addGlobalWarning("Translation not applicable for $transId, maybe module has been deleted");
                        }
                    }
                }
            }

            $result->setFlattenTranslations($currentFlattenTranslations);
        }

        return $results;
    }

    public function exportFile(array $flattenTranslations, string $domain, string $targetLocale, string $fallbackLocale, array $options = []): File
    {
        $fileName = "$domain.$targetLocale.xlf";

        if ($options['exportFileNameBase'] ?? false) {
            $fileName = "{$options['exportFileNameBase']}.$targetLocale.xlf";
        }

        $file = new File(sys_get_temp_dir()."/$fileName", false);
        file_put_contents($file->getPathname(), $this->exportXml($flattenTranslations, $domain, $targetLocale, $fallbackLocale, $options));

        return $file;
    }

    /**
     * @throws ExportException
     */
    protected function exportXml(array $flattenTranslations, string $domain, string $targetLocale, string $fallbackLocale, array $options = []): string
    {
        try {
            $pluginVersion = InstalledVersions::getVersion('softspring/cms-translation-plugin');

            $toolInfo = ['tool-id' => 'sfs-cms-translation-plugin', 'tool-name' => 'SfsCms Translation Plugin', 'tool-version' => $pluginVersion];

            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->formatOutput = true;

            $xliff = $dom->appendChild($dom->createElement('xliff'));
            $xliff->setAttribute('version', '1.2');
            $xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');

            $xliffFile = $xliff->appendChild($dom->createElement('file'));
            $xliffFile->setAttribute('source-language', str_replace('_', '-', $fallbackLocale));
            $xliffFile->setAttribute('target-language', str_replace('_', '-', $targetLocale));
            $xliffFile->setAttribute('datatype', 'plaintext');
            $xliffFile->setAttribute('original', $domain);

            $xliffHead = $xliffFile->appendChild($dom->createElement('header'));
            $xliffTool = $xliffHead->appendChild($dom->createElement('tool'));
            foreach ($toolInfo as $id => $value) {
                $xliffTool->setAttribute($id, $value);
            }

            if ($options['ref'] ?? false) {
                $reference = $xliffHead->appendChild($dom->createElement('reference'));
                if (isset($options['ref']['class'])) {
                    $reference->setAttribute('class', $options['ref']['class']);
                }
                if (isset($options['ref']['id'])) {
                    $reference->setAttribute('id', $options['ref']['id']);
                }
                if (isset($options['ref']['version'])) {
                    $reference->setAttribute('version', $options['ref']['version']);
                }
            }

            $xliffBody = $xliffFile->appendChild($dom->createElement('body'));
            $currentModule = null;
            foreach ($flattenTranslations as $fieldKey => $fieldTranslation) {
                $fieldTranslation = $fieldTranslation instanceof Translation ? $fieldTranslation->__toArray() : $fieldTranslation;

                $fieldKeyParts = explode(':', $fieldKey);
                if ('_module' === array_pop($fieldKeyParts)) {
                    $currentModule = $fieldTranslation;
                    continue;
                }

                $translationMessage = $fieldTranslation[$targetLocale] ?? '';

                $unit = $dom->createElement('trans-unit');

                // trans-id values
                // 1st: field _trans_id unique value
                // 2nd: entity id + field key
                // 3rd: domain + field key
                $transId = $fieldTranslation['_trans_id'] ?? (isset($options['ref']['id']) ? "{$options['ref']['id']}.$fieldKey" : "$domain.$fieldKey");

                $unit->setAttribute('id', $transId);
                $unit->setAttribute('resname', $fieldKey);
                $currentModule && $unit->setAttribute('data-module', $currentModule);

                $unitSource = $unit->appendChild($dom->createElement('source'));
                $fallbackMessage = $fieldTranslation[$fallbackLocale] ?? '';
                $unitSource->appendChild($this->createTextNode($dom, $fallbackMessage));

                $unitTarget = $unit->appendChild($dom->createElement('target'));
                foreach ($fieldTranslation['_metadata'][$targetLocale] ?? [] as $name => $value) {
                    $unitTarget->setAttribute($name, $value);
                }
                $unitTarget->appendChild($this->createTextNode($dom, $translationMessage));

                if (!empty($fieldTranslation['_notes'])) {
                    foreach ($fieldTranslation['_notes'] as $note) {
                        if (!isset($note['content'])) {
                            continue;
                        }

                        $n = $unit->appendChild($dom->createElement('note'));
                        $n->appendChild($dom->createTextNode($note['content']));

                        if (isset($note['priority'])) {
                            $n->setAttribute('priority', $note['priority']);
                        }

                        if (isset($note['from'])) {
                            $n->setAttribute('from', $note['from']);
                        }
                    }
                }

                $xliffBody->appendChild($unit);
            }

            return $dom->saveXML();
        } catch (Exception $e) {
            throw new ExportException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function createTextNode(DOMDocument $dom, string $text): DOMText
    {
        return 1 === preg_match('/[&<>]/', $text) ? $dom->createCDATASection($text) : $dom->createTextNode($text);
    }
}
