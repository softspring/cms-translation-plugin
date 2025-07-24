<?php

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion;

use Softspring\CmsBundle\Admin\ActionListener\ContentVersion\AbstractContentVersionListener;
use Softspring\CmsBundle\Helper\CmsHelper;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Manager\ContentVersionManagerInterface;
use Softspring\CmsBundle\Manager\RouteManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
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

class ExportListener extends AbstractContentVersionListener
{
    protected const ACTION_NAME = 'version_translations_export';

    public function __construct(
        ContentManagerInterface $contentManager,
        ContentVersionManagerInterface $contentVersionManager,
        RouteManagerInterface $routeManager,
        CmsHelper $cmsHelper,
        RouterInterface $router,
        FlashNotifier $flashNotifier,
        AuthorizationCheckerInterface $authorizationChecker,
        protected TranslatorExtractor $translatorExtractor,
        protected TranslatableContext $translatableContext,
        protected Exchanger $exchanger,
    ) {
        parent::__construct($contentManager, $contentVersionManager, $routeManager, $cmsHelper, $router, $flashNotifier, $authorizationChecker);
    }

    public static function getSubscribedEvents(): array
    {
        return [
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
        ];
    }

    public function onEntityLoadVersionEntity(LoadEntityEvent $event): void
    {
        $request = $event->getRequest();

        /** @var ContentInterface $content */
        $content = $request->attributes->get('content');
        $version = $request->query->get('version');

        if ($version) {
            $version = $this->contentVersionManager->getRepository()->findOneBy(['content' => $content, 'id' => $version]);
        }

        if (!$version) {
            $version = $content->getLastVersion();
        }

        $request->attributes->set('version', $version);
        $event->setEntity($version);
        $event->setNotFound(!$version);
    }

    public function onApply(ApplyEvent $event): void
    {
        /** @var ContentVersionInterface $entity */
        $entity = $event->getEntity();

        $fallbackLocale = $entity->getContent()->getDefaultLocale();
        $targetLocale = $event->getRequest()->attributes->get('target');
        $format = $event->getRequest()->attributes->get('format');

        $dataTranslations = $this->translatorExtractor->extract($entity);
        $flattenTranslations = TranslationsTransformer::flatten($dataTranslations);

        $exportOptions = [
            'exportFileNameBase' => "{$entity->getContent()->getName()} (v{$entity->getVersionNumber()})",
            'ref' => [
                'id' => $entity->getContent()->getId(),
                'class' => get_class($entity->getContent()),
                'version' => $entity->getVersionNumber(),
            ],
        ];

        if ('all' === $targetLocale) {
            $filePath = sys_get_temp_dir()."/{$exportOptions['exportFileNameBase']}.zip";
            file_exists($filePath) && unlink($filePath);
            $file = new File($filePath, false);
            $zipFile = new ZipArchive();
            $zipFile->open($file->getPathname(), ZipArchive::CREATE);
            foreach ($entity->getContent()->getLocales() as $locale) {
                $localeFile = $this->exchanger->get($format)->exportFile($flattenTranslations, "{$entity->getContent()->getId()}_v{$entity->getVersionNumber()}", $locale, $fallbackLocale, $exportOptions);
                $zipFile->addFile($localeFile->getPathname(), $localeFile->getBasename());
            }
            $zipFile->close();
        } else {
            $file = $this->exchanger->get($format)->exportFile($flattenTranslations, "{$entity->getContent()->getId()}_v{$entity->getVersionNumber()}", $targetLocale, $fallbackLocale, $exportOptions);
        }

        $event->getRequest()->attributes->set('file', $file);
        $event->setApplied(true);
    }

    public function onSuccess(SuccessEvent $event): void
    {
        /** @var File $file */
        $file = $event->getRequest()->attributes->get('file');

        $response = new BinaryFileResponse($file, 200, [], true, 'attachment');
        $response->headers->set('Content-Type', $file->getMimeType());
        $event->setResponse($response);
    }

    public function onFailure(FailureEvent $event): void
    {
        $contentConfig = $event->getRequest()->attributes->get('_content_config');

        $this->flashNotifier->add('error', $event->getException()->getMessage());

        $url = $this->router->generate("sfs_cms_admin_content_{$event->getRequest()->attributes->get('_content_config')['_id']}_translations", ['content' => $event->getRequest()->attributes->get('content')]);
        $event->setResponse(new RedirectResponse($url));
    }

    public function onException(ExceptionEvent $event): void
    {
        $contentConfig = $event->getRequest()->attributes->get('_content_config');

        $this->flashNotifier->add('error', $event->getException()->getMessage());

        $url = $this->router->generate("sfs_cms_admin_content_{$event->getRequest()->attributes->get('_content_config')['_id']}_translations", ['content' => $event->getRequest()->attributes->get('content')]);
        $event->setResponse(new RedirectResponse($url));
    }
}
