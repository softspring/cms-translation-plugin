import {addTargetEventListener, registerFeature, callForeachSelector} from '@softspring/cms-bundle/scripts/tools';

registerFeature('_translation_plugin__admin_version_translations', _init);

/**
 * Init behaviour
 * @private
 */
function _init() {
    addTargetEventListener('[data-locale-trans-toggler]', 'click', onLocaleChickToggleTranslationsColumn, 1);
}

function onLocaleChickToggleTranslationsColumn(translateButton) {
    const targetLocale = translateButton.getAttribute('data-locale-trans-toggler');

    callForeachSelector(`[data-locale-trans=${targetLocale}]`, function (row) {
        row.classList.remove('d-none');
        row.classList.remove('hidden');
    });

    callForeachSelector(`[data-locale-trans]:not([data-locale-trans=${targetLocale}])`, function (row) {
        row.classList.add('d-none');
        row.classList.add('hidden');
    });
}
