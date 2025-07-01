<?php

namespace Softspring\CmsTranslationPlugin\Admin\ActionListener\SectionVersion;

use Exception;
use Softspring\CmsBundle\Admin\ActionListener\SectionVersion\AbstractSectionVersionListener;
use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;
use Softspring\CmsBundle\Manager\RouteManagerInterface;
use Softspring\CmsBundle\Manager\SectionManagerInterface;
use Softspring\CmsBundle\Manager\SectionVersionManagerInterface;
use Softspring\CmsBundle\Model\SectionInterface;
use Softspring\CmsBundle\Model\SectionVersionInterface;
use Softspring\CmsBundle\Request\FlashNotifier;
use Softspring\CmsBundle\Translator\TranslatableContext;
use Softspring\CmsTranslationPlugin\Exchange\Exchanger;
use Softspring\CmsTranslationPlugin\Exchange\ImportException;
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

class ImportListener extends AbstractSectionVersionListener
{
    protected const ACTION_NAME = 'version_translations_import';

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
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_INITIALIZE => [
                ['onLoadSectionEntity', 9],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_ENTITY => [
                ['onCreateEntity', 0],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FORM_PREPARE => [
                ['onFormPrepareResolve', 0],
            ],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FORM_INIT => [],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FORM_VALID => [],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_APPLY => [
                ['onApply', 0],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_SUCCESS => [
                ['onSuccess', 0],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FAILURE => [
                ['onFailure', 0],
            ],
            // SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_FORM_INVALID => [],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_VIEW => [
                ['onViewAddEntities', 0],
                ['onViewAddPrevVersion', 0],
            ],
            SfsCmsTranslationPlugin::ADMIN_SECTION_VERSIONS_TRANSLATIONS_IMPORT_EXCEPTION => [
                ['onException', 0],
            ],
        ];
    }

    public function onCreateEntity(CreateEntityEvent $event): void
    {
        $versionId = $event->getRequest()->get('version');

        /** @var SectionInterface $section */
        $section = $event->getRequest()->attributes->get('section');

        $version = $section->getVersions()->filter(fn (SectionVersionInterface $versionI) => $versionI->getId() === $versionId)->first();
        $event->getRequest()->attributes->set('version', $version);

        // no entity is required for form
        $event->setEntity([]);
    }

    public function onFormPrepareResolve(FormPrepareEvent $event): void
    {
        $event->setFormOptions([
            'method' => 'POST',
            //            'section_config' => $sectionConfig,
            //            'section_type' => $sectionConfig['_id'],
            //            'section' => $event->getRequest()->attributes->get('section'),
        ]);

        $event->setData(null);
    }

    /**
     * @throws InvalidContentException
     * @throws ExtractException
     * @throws ImportException
     */
    public function onApply(ApplyEvent $event): void
    {
        $request = $event->getRequest();
        /** @var SectionInterface $section */
        $section = $request->attributes->get('section');
        /** @var ?SectionVersionInterface $version */
        $version = $request->attributes->get('version');

        $file = $event->getForm()->get('file')->getData();
        $dataTranslations = $this->translatorExtractor->extract($version);
        $flattenTranslations = TranslationsTransformer::flatten($dataTranslations);

        $importResults = $this->exchanger->getImporter($file)->importFile($file, $flattenTranslations);
        $importResult = $importResults->getResults()[0];

        if ($importResult->getEntityId() != $section->getId()) {
            $event->getForm()->addError(new FormError('Imported translations are not for the selected section (ids do not match)'));
            throw new Exception();
        }

        if ($importResult->getEntityClass() != get_class($section)) {
            $event->getForm()->addError(new FormError('Imported translations are not for the selected section type'));
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

    public function onViewAddPrevVersion(ViewEvent $event): void
    {
        $request = $event->getRequest();
        /** @var SectionInterface $section */
        $section = $request->attributes->get('section');
        /** @var SectionVersionInterface $version */
        $version = $request->attributes->get('version');

        $event->getData()['prev_version'] = $version;
    }

    public function onSuccess(SuccessEvent $event): void
    {
        $request = $event->getRequest();
        /** @var SectionInterface $section */
        $section = $request->attributes->get('section');

        $url = $this->router->generate(name: 'sfs_cms_admin_sections_translations', parameters: ['section' => $section, 'version' => $request->query->get('version')]);
        $event->setResponse(new RedirectResponse($url));
    }

    public function onFailure(FailureEvent $event): void
    {
        //        $sectionConfig = $event->getRequest()->attributes->get('_section_config');
        //        $request = $event->getRequest();
        //        /** @var SectionInterface $section */
        //        $section = $request->attributes->get('section');
        //
        //        $url = $this->router->generate(name: "sfs_cms_admin_sections_translations", parameters: ['section' => $section]);
        //        $event->setResponse(new RedirectResponse($url));
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        /** @var SectionInterface $section */
        $section = $request->attributes->get('section');

        $url = $this->router->generate(name: 'sfs_cms_admin_sections_translations', parameters: ['section' => $section]);
        $event->setResponse(new RedirectResponse($url));
    }
}
