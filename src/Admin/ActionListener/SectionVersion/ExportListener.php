<?php

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion;

use Softspring\CmsBundle\Admin\ActionListener\SectionVersion\AbstractSectionVersionListener;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Manager\RouteManagerInterface;
use Softspring\CmsBundle\Manager\SectionManagerInterface;
use Softspring\CmsBundle\Manager\SectionVersionManagerInterface;
use Softspring\CmsBundle\Model\SectionInterface;
use Softspring\CmsBundle\Model\SectionVersionInterface;
use Softspring\CmsBundle\Request\FlashNotifier;
use Softspring\CmsBundle\Translator\TranslatableContext;
use Softspring\CmsTranslationPlugin\Exchange\Exchanger;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;
use Softspring\CmsTranslationPlugin\Translator\TranslationsTransformer;
use Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor;
use Softspring\Component\CrudlController\Event\ApplyEvent;
use Softspring\Component\CrudlController\Event\ExceptionEvent;
use Softspring\Component\CrudlController\Event\FailureEvent;
use Softspring\Component\CrudlController\Event\LoadEntityEvent;
use Softspring\Component\CrudlController\Event\SuccessEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use ZipArchive;

class ExportListener extends AbstractSectionVersionListener
{
    protected const ACTION_NAME = 'version_translations_export';

    public function __construct(
        SectionManagerInterface $sectionManager,
        SectionVersionManagerInterface $sectionVersionManager,
        RouteManagerInterface $routeManager,
        CmsConfig $cmsConfig,
        RouterInterface $router,
        FlashNotifier $flashNotifier,
        AuthorizationCheckerInterface $authorizationChecker,
        protected TranslatorExtractor $translatorExtractor,
        protected TranslatableContext $translatableContext,
        protected Exchanger $exchanger,
    ) {
        parent::__construct($sectionManager, $sectionVersionManager, $routeManager, $cmsConfig, $router, $flashNotifier, $authorizationChecker);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_INITIALIZE => [
                ['onEventLoadSectionEntity', 9],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_ENTITY => [
                ['onEntityLoadVersionEntity', 1],
            ],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_NOT_FOUND => [],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_EXPORT_FOUND => [],
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
        ];
    }

    public function onEntityLoadVersionEntity(LoadEntityEvent $event): void
    {
        $request = $event->getRequest();

        /** @var SectionInterface $section */
        $section = $request->attributes->get('section');
        $version = $request->query->get('version');

        if ($version) {
            $version = $section->getVersions()->filter(fn (SectionVersionInterface $versionItem) => $versionItem->getId() == $version)->first();
        }

        if (!$version) {
            $version = $section->getLastVersion();
        }

        $request->attributes->set('version', $version);
        $event->setEntity($version);
        $event->setNotFound(!$version);
    }

    public function onApply(ApplyEvent $event): void
    {
        /** @var SectionVersionInterface $entity */
        $entity = $event->getEntity();

        $fallbackLocale = $entity->getSection()->getDefaultLocale();
        $targetLocale = $event->getRequest()->attributes->get('target');
        $format = $event->getRequest()->attributes->get('format');

        $dataTranslations = $this->translatorExtractor->extract($entity);
        $flattenTranslations = TranslationsTransformer::flatten($dataTranslations);

        $exportOptions = [
            'exportFileNameBase' => "{$entity->getSection()->getName()} (v{$entity->getVersionNumber()})",
            'ref' => [
                'id' => $entity->getSection()->getId(),
                'class' => get_class($entity->getSection()),
                'version' => $entity->getVersionNumber(),
            ],
        ];

        if ('all' === $targetLocale) {
            $filePath = sys_get_temp_dir()."/{$exportOptions['exportFileNameBase']}.zip";
            file_exists($filePath) && unlink($filePath);
            $file = new File($filePath, false);
            $zipFile = new ZipArchive();
            $zipFile->open($file->getPathname(), ZipArchive::CREATE);
            foreach ($entity->getSection()->getLocales() as $locale) {
                $localeFile = $this->exchanger->get($format)->exportFile($flattenTranslations, "{$entity->getSection()->getId()}_v{$entity->getVersionNumber()}", $locale, $fallbackLocale, $exportOptions);
                $zipFile->addFile($localeFile->getPathname(), $localeFile->getBasename());
            }
            $zipFile->close();
        } else {
            $file = $this->exchanger->get($format)->exportFile($flattenTranslations, "{$entity->getSection()->getId()}_v{$entity->getVersionNumber()}", $targetLocale, $fallbackLocale, $exportOptions);
        }

        $event->getRequest()->attributes->set('file', $file);
        $event->setApplied(true);
    }

    public function onSuccess(SuccessEvent $event): void
    {
        /** @var File $file */
        $file = $event->getRequest()->attributes->get('file');

        $response = new BinaryFileResponse($file, 200, [], true, 'attachment');
        $response->headers->set('Section-Type', $file->getMimeType());
        $event->setResponse($response);
    }

    public function onFailure(FailureEvent $event): void
    {
        $this->flashNotifier->add('error', $event->getException()->getMessage());

        $url = $this->router->generate('sfs_cms_admin_sections_translations', ['section' => $event->getRequest()->attributes->get('section')]);
        $event->setResponse(new RedirectResponse($url));
    }

    public function onException(ExceptionEvent $event): void
    {
        $this->flashNotifier->add('error', $event->getException()->getMessage());

        $url = $this->router->generate('sfs_cms_admin_sections_translations', ['section' => $event->getRequest()->attributes->get('section')]);
        $event->setResponse(new RedirectResponse($url));
    }
}
