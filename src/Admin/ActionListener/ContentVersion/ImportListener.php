<?php

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\ContentVersion;

use Exception;
use Softspring\CmsBundle\Admin\ActionListener\ContentVersion\AbstractContentVersionListener;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Manager\ContentVersionManagerInterface;
use Softspring\CmsBundle\Manager\RouteManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsBundle\Request\FlashNotifier;
use Softspring\CmsBundle\Translator\TranslatableContext;
use Softspring\CmsTranslationPlugin\Exchange\Exchanger;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;
use Softspring\CmsTranslationPlugin\Translator\ExtractException;
use Softspring\CmsTranslationPlugin\Translator\TranslationsTransformer;
use Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor;
use Softspring\Component\CrudlController\Event\ApplyEvent;
use Softspring\Component\CrudlController\Event\CreateEntityEvent;
use Softspring\Component\CrudlController\Event\ExceptionEvent;
use Softspring\Component\CrudlController\Event\FailureEvent;
use Softspring\Component\CrudlController\Event\FormPrepareEvent;
use Softspring\Component\CrudlController\Event\SuccessEvent;
use Softspring\Component\CrudlController\Event\ViewEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ImportListener extends AbstractContentVersionListener
{
    protected const ACTION_NAME = 'version_translations_import';

    public function __construct(
        ContentManagerInterface $contentManager,
        ContentVersionManagerInterface $contentVersionManager,
        RouteManagerInterface $routeManager,
        CmsConfig $cmsConfig,
        RouterInterface $router,
        FlashNotifier $flashNotifier,
        AuthorizationCheckerInterface $authorizationChecker,
        protected TranslatorExtractor $translatorExtractor,
        protected TranslatableContext $translatableContext,
        protected Exchanger $exchanger,
    ) {
        parent::__construct($contentManager, $contentVersionManager, $routeManager, $cmsConfig, $router, $flashNotifier, $authorizationChecker);
    }

    public static function getSubscribedEvents(): array
    {
        return [
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
        ];
    }

    public function onCreateEntity(CreateEntityEvent $event): void
    {
        $versionId = $event->getRequest()->get('version');

        /** @var ContentInterface $content */
        $content = $event->getRequest()->attributes->get('content');

        $version = $content->getVersions()->filter(fn (ContentVersionInterface $versionI) => $versionI->getId() === $versionId)->first();
        $event->getRequest()->attributes->set('version', $version);

        // no entity is required for form
        $event->setEntity([]);
    }

    public function onFormPrepareResolve(FormPrepareEvent $event): void
    {
        $contentConfig = $event->getRequest()->attributes->get('_content_config');

        $event->setType($this->getOption($event->getRequest(), 'type'));
        $event->setFormOptions([
            'method' => 'POST',
            'content_config' => $contentConfig,
            'content_type' => $contentConfig['_id'],
            'content' => $event->getRequest()->attributes->get('content'),
        ]);

        $event->setData(null);
    }

    /**
     * @throws InvalidContentException
     * @throws ExtractException
     */
    public function onApply(ApplyEvent $event): void
    {
        $request = $event->getRequest();
        /** @var ContentInterface $content */
        $content = $request->attributes->get('content');
        /** @var ?ContentVersionInterface $version */
        $version = $request->attributes->get('version');

        $file = $event->getForm()->get('file')->getData();
        $dataTranslations = $this->translatorExtractor->extract($version);
        $flattenTranslations = TranslationsTransformer::flatten($dataTranslations);

        $importResults = $this->exchanger->getImporter($file)->importFile($file, $flattenTranslations);
        $importResult = $importResults->getResults()[0];

        if ($importResult->getEntityId() != $content->getId()) {
            $event->getForm()->addError(new FormError('Imported translations are not for the selected content (ids do not match)'));
            throw new Exception();
        }

        if ($importResult->getEntityClass() != get_class($content)) {
            $event->getForm()->addError(new FormError('Imported translations are not for the selected content type'));
            throw new Exception();
        }

        if ($importResult->getVersionNumber() != $version->getVersionNumber()) {
            $importResult->addGlobalWarning("Imported translations were for a different version number (v{$importResult->getVersionNumber()}) than the target one (v{$version->getVersionNumber()})");
        }

        $session = $request->getSession();
        $session->set('_translations_imported', $importResult->getFlattenTranslations());
        $session->set('_translations_global_warnings', $importResult->getGlobalWarnings());
        $session->set('_translations_field_warnings', $importResult->getFieldWarnings());
        $session->set('_translations_changelog', $importResult->getChangeLog());

        $event->setApplied(true);
    }

    public function onView(ViewEvent $event): void
    {
        parent::onView($event);

        $request = $event->getRequest();
        /** @var ContentInterface $content */
        $content = $request->attributes->get('content');
        /** @var ContentVersionInterface $version */
        $version = $request->attributes->get('version');

        $event->getData()['content_entity'] = $content;
        $event->getData()['version_entity'] = $version;
        $event->getData()['prev_version'] = $version;
    }

    public function onSuccess(SuccessEvent $event): void
    {
        $contentConfig = $event->getRequest()->attributes->get('_content_config');
        $request = $event->getRequest();
        /** @var ContentInterface $content */
        $content = $request->attributes->get('content');

        $url = $this->router->generate(name: "sfs_cms_admin_content_{$contentConfig['_id']}_translations", parameters: ['content' => $content, 'version' => $request->query->get('version')]);
        $event->setResponse(new RedirectResponse($url));
    }

    public function onFailure(FailureEvent $event): void
    {
        //        $contentConfig = $event->getRequest()->attributes->get('_content_config');
        //        $request = $event->getRequest();
        //        /** @var ContentInterface $content */
        //        $content = $request->attributes->get('content');
        //
        //        $url = $this->router->generate(name: "sfs_cms_admin_content_{$contentConfig['_id']}_translations", parameters: ['content' => $content]);
        //        $event->setResponse(new RedirectResponse($url));
    }

    public function onException(ExceptionEvent $event): void
    {
        $contentConfig = $event->getRequest()->attributes->get('_content_config');
        $request = $event->getRequest();
        /** @var ContentInterface $content */
        $content = $request->attributes->get('content');

        $url = $this->router->generate(name: "sfs_cms_admin_content_{$contentConfig['_id']}_translations", parameters: ['content' => $content]);
        $event->setResponse(new RedirectResponse($url));
    }
}
