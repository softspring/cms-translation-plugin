<?php

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion;

use Softspring\CmsBundle\Helper\CmsHelper;
use Softspring\CmsBundle\Manager\RouteManagerInterface;
use Softspring\CmsBundle\Render\RenderErrorException;
use Softspring\CmsBundle\Request\FlashNotifier;
use Softspring\CmsBundle\Translator\TranslatableContext;
use Softspring\CmsSectionsPlugin\Admin\ActionListener\SectionVersion\AbstractSectionVersionListener;
use Softspring\CmsSectionsPlugin\Manager\SectionManagerInterface;
use Softspring\CmsSectionsPlugin\Manager\SectionVersionManagerInterface;
use Softspring\CmsSectionsPlugin\Model\SectionInterface;
use Softspring\CmsSectionsPlugin\Model\SectionVersionInterface;
use Softspring\CmsTranslationPlugin\SfsCmsTranslationPlugin;
use Softspring\CmsTranslationPlugin\Translator\ExtractException;
use Softspring\CmsTranslationPlugin\Translator\InvalidTranslationMappingException;
use Softspring\CmsTranslationPlugin\Translator\TranslationsTransformer;
use Softspring\CmsTranslationPlugin\Translator\TranslatorExtractor;
use Softspring\Component\CrudlController\Event\ApplyEvent;
use Softspring\Component\CrudlController\Event\CreateEntityEvent;
use Softspring\Component\CrudlController\Event\ExceptionEvent;
use Softspring\Component\CrudlController\Event\FailureEvent;
use Softspring\Component\CrudlController\Event\FormInvalidEvent;
use Softspring\Component\CrudlController\Event\FormPrepareEvent;
use Softspring\Component\CrudlController\Event\SuccessEvent;
use Softspring\Component\CrudlController\Event\ViewEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TranslationsListener extends AbstractSectionVersionListener
{
    protected const ACTION_NAME = 'version_translations';

    public function __construct(SectionManagerInterface $sectionManager, SectionVersionManagerInterface $sectionVersionManager, RouteManagerInterface $routeManager, CmsHelper $cmsHelper, RouterInterface $router, FlashNotifier $flashNotifier, AuthorizationCheckerInterface $authorizationChecker, protected TranslatorExtractor $translatorExtractor, protected TranslatableContext $translatableContext)
    {
        parent::__construct($sectionManager, $sectionVersionManager, $routeManager, $cmsHelper, $router, $flashNotifier, $authorizationChecker);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_INITIALIZE => [
                ['onLoadSectionEntity', 9],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_ENTITY => [
                ['onTranslationsLoadEntity', 1],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_FORM_PREPARE => [
                ['onFormPrepareResolve', 0],
            ],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_FORM_INIT => [],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_FORM_VALID => [],
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
        ];
    }

    public function onTranslationsLoadEntity(CreateEntityEvent $event): void
    {
        $request = $event->getRequest();

        /** @var SectionInterface $section */
        $section = $request->attributes->get('section');
        $prevVersion = $request->query->get('version');

        if ($prevVersion) {
            $prevVersion = $section->getVersions()->filter(fn (SectionVersionInterface $version): bool => $version->getId() == $prevVersion)->first();
        }

        $request->attributes->set('prevVersion', $prevVersion ?: $section->getLastVersion());
        $version = $this->sectionManager->createVersion($section, $prevVersion, SectionVersionInterface::ORIGIN_TRANSLATIONS);
        $prevVersion && $version->setOriginDescription('v'.$prevVersion->getVersionNumber());

        $request->attributes->set('version', $version);

        $event->setEntity($version);
    }

    /**
     * @throws ExtractException
     */
    public function onFormPrepareResolve(FormPrepareEvent $event): void
    {
        /** @var SectionVersionInterface $version */
        $version = $event->getEntity();

        $this->translatableContext->setDefaultLocale($version->getSection()->getDefaultLocale());
        $this->translatableContext->setLocales($version->getSection()->getLocales());
        $flattenTranslations = TranslationsTransformer::flatten($this->translatorExtractor->extract($version));

        $session = $event->getRequest()->getSession();
        if ($session->has('_translations_imported')) {
            $importedFlattenTranslations = $session->get('_translations_imported');
            $importedFlattenChangelog = $session->get('_translations_changelog');

            // TODO merge with current translations
            $flattenTranslationsBeforeImporting = $flattenTranslations;
            $flattenTranslations = $importedFlattenTranslations;

            $event->getRequest()->attributes->set('_translations_imported', true);

            $session->remove('_translations_imported');
            $session->remove('_translations_changelog');
        }

        $event->setFormOptions([
            'method' => 'POST',
            'flatten_translations' => $flattenTranslations,
            'flatten_translations_before_importing' => $flattenTranslationsBeforeImporting ?? null,
        ]);

        // set data for form
        $event->setData($flattenTranslations);
    }

    /**
     * @throws InvalidTranslationMappingException
     */
    public function onApply(ApplyEvent $event): void
    {
        $version = $event->getEntity();
        $flattenTranslations = $event->getForm()->getData();

        $version->setData(TranslationsTransformer::applyFlatten($version->getData(), $flattenTranslations));

        $event->setApplied(false); // do save entity
    }

    /**
     * @noinspection PhpRouteMissingInspection
     */
    public function onSuccess(SuccessEvent $event): void
    {
        $request = $event->getRequest();
        $version = $event->getEntity();
        $section = $version->getSection();

        if ($version->hasCompileErrors()) {
            $this->flashNotifier->addTrans('warning', 'admin_sections.version_translations.success_saved_with_compile_errors', [], 'sfs_cms_sections');
        } else {
            $this->flashNotifier->addTrans('success', 'admin_sections.version_translations.success_saved', [], 'sfs_cms_sections');
        }

        switch ($request->request->get('goto')) {
            case 'section':
                $url = $this->router->generate('sfs_cms_admin_sections_section', ['section' => $section, 'saved' => 1]);
                $event->setResponse(new RedirectResponse($url));
                break;

            case 'preview':
                $url = $this->router->generate('sfs_cms_admin_sections_preview', ['section' => $section]);
                $event->setResponse(new RedirectResponse($url));
                break;

            case 'publish':
                $url = $this->router->generate('sfs_cms_admin_sections_publish_version', ['section' => $section, 'version' => $version]);
                $event->setResponse(new RedirectResponse($url));
                break;

            default:
                $event->setResponse($this->redirectBack($section, $request));
        }
    }

    public function onFailureShowAlert(FailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        if ($exception instanceof RenderErrorException) {
            $exception->getRenderErrorList()->formMapErrors($event->getForm());

            $request->attributes->set('_section_version_alert', ['error', 'admin_sections.version_translations.render_error']);
        }
    }

    public function onFormInvalidShowAlert(FormInvalidEvent $event): void
    {
        $request = $event->getRequest();
        $request->attributes->set('_section_version_alert', ['warning', 'admin_sections.version_translations.validation_error']);
    }

    public function onViewAddVariables(ViewEvent $event): void
    {
        $request = $event->getRequest();

        // add prev version
        $event->getData()['prev_version'] = $request->attributes->get('prevVersion');

        if ($request->getSession()->has('_translations_global_warnings')) {
            $event->getData()['globalImportWarnings'] = $request->getSession()->get('_translations_global_warnings');
            $request->getSession()->remove('_translations_global_warnings');
        }

        if ($request->getSession()->has('_translations_field_warnings')) {
            $event->getData()['fieldImportWarnings'] = $request->getSession()->get('_translations_field_warnings');
            $request->getSession()->remove('_translations_field_warnings');
        }
    }

    /**
     * @noinspection PhpRouteMissingInspection
     */
    public function onException(ExceptionEvent $event): void
    {
        if ($event->getException() instanceof InvalidTranslationMappingException) {
            // TODO manage this

            return;
        }

        if ($event->getException() instanceof ExtractException) {
        }
    }
}
