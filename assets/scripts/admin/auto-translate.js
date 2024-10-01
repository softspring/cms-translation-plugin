import {registerFeature} from '@softspring/cms-bundle/scripts/tools';

registerFeature('_translation_plugin__admin_auto_translate', _init);

/**
 * Init behaviour
 * @private
 */
function _init() {
    document.addEventListener('click', onDataTranslateClick);
}

function onDataTranslateClick(event) {
    let dataTranslate = null;
    if (event.target.hasAttribute('data-translate')) {
        dataTranslate = event.target;
    } else if (event.target.parentNode && event.target.parentNode.hasAttribute('data-translate')) {
        dataTranslate = event.target.parentNode;
    }
    if (dataTranslate) {
        return doTranslation(dataTranslate);
    }

    let dataTranslateAll = null;
    if (event.target.hasAttribute('data-translate-all-target-locale')) {
        dataTranslateAll = event.target;
    } else if (event.target.parentNode && event.target.parentNode.hasAttribute('data-translate-all-target-locale')) {
        dataTranslateAll = event.target.parentNode;
    }
    if (dataTranslateAll) {
        let targetLocale = dataTranslateAll.getAttribute('data-translate-all-target-locale');
        let translateButtons = document.querySelectorAll('[data-translate-target-locale="' + targetLocale + '"]')
        translateButtons.forEach(function (translateButton) {
            let targetField = document.querySelector('[name="' + translateButton.getAttribute('data-translate-target-field') + '"]');
            (targetField.value === "") && translateButton.click()
        });
    }
}

function doTranslation(translateButton) {
    const sourceField = document.querySelector('[name="' + translateButton.getAttribute('data-translate-source-field') + '"]');
    const targetField = document.querySelector('[name="' + translateButton.getAttribute('data-translate-target-field') + '"]');
    const sourceLocale = translateButton.getAttribute('data-translate-source-locale');
    const targetLocale = translateButton.getAttribute('data-translate-target-locale');
    const translateUrl = translateButton.getAttribute('data-translate-url');

    if (!sourceField.value) {
        console.log('Skipping translation, source field is empty');
        return;
    }

    targetField.classList.remove('sfs-cms-translated');
    targetField.classList.remove('sfs-cms-translation-error');
    targetField.classList.add('sfs-cms-is-translating');

    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        targetField.classList.remove('sfs-cms-is-translating');
        try {
            var data = JSON.parse(xhr.responseText);

            if (xhr.status >= 200 && xhr.status < 300) {
                console.log('TRANSLATION RESPONSE', data);
                // target.value = data.data.text;
                targetField.value = data.data.translation;
                const inputEvent = new Event('input', {bubbles: true});
                // inputEvent.target = targetField;
                targetField.dispatchEvent(inputEvent);

                targetField.classList.add('sfs-cms-translated');
            } else {
                console.log('TRANSLATION ERROR', data.error);
                targetField.classList.add('sfs-cms-translation-error');
            }
        } catch (e) {
            console.error('Error parsing translation response', e);
            targetField.classList.add('sfs-cms-translation-error');
        }
    };
    xhr.onerror = function (error) {
        console.error('xhr error', error);
        targetField.classList.add('sfs-cms-translation-error');
    };
    xhr.open('POST', translateUrl);
    let data = new FormData();
    data.append('text', sourceField.value);
    data.append('source', sourceLocale);
    data.append('target', targetLocale);
    xhr.send(data);
}