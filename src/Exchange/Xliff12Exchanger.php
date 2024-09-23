<?php

namespace Softspring\CmsTranslationPlugin\Exchange;

use Composer\InstalledVersions;
use DOMDocument;
use DOMText;
use Symfony\Component\HttpFoundation\File\File;

class Xliff12Exchanger implements ExchangerInterface
{
    public static function format(): string
    {
        return 'xliff12';
    }

    public function importFile()
    {
        // TODO: Implement import() method.
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
                isset($options['ref']['class']) && $reference->setAttribute('class', $options['ref']['class']);
                isset($options['ref']['id']) && $reference->setAttribute('id', $options['ref']['id']);
                isset($options['ref']['version']) && $reference->setAttribute('version', $options['ref']['version']);
            }

            $xliffBody = $xliffFile->appendChild($dom->createElement('body'));
            $currentModule = null;
            foreach ($flattenTranslations as $fieldKey => $fieldTranslation) {
                $fieldKeyParts = explode(':', $fieldKey);
                if ('_module' === array_pop($fieldKeyParts)) {
                    $currentModule = $fieldTranslation;
                    continue;
                }

                $translationMessage = $fieldTranslation[$targetLocale] ?? '';

                $unit = $dom->createElement('trans-unit');

                $unit->setAttribute('id', $fieldTranslation['_trans_id'] ?? "$domain.$fieldKey");
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
        } catch (\Exception $e) {
            throw new ExportException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function createTextNode(DOMDocument $dom, string $text): DOMText
    {
        return 1 === preg_match('/[&<>]/', $text) ? $dom->createCDATASection($text) : $dom->createTextNode($text);
    }
}
