window.addEventListener('load', _init);

function _init() {
    if (typeof translateUrl === 'undefined') {
        // console.error('Translate API is disabled, missing translateUrl variable');
        return;
    }

    document.addEventListener('click', function (event) {
        if (event.target.hasAttribute('data-translate')) {
            doTranslation(event.target);
        } else if (event.target.parentNode && event.target.parentNode.hasAttribute('data-translate')) {
            doTranslation(event.target.parentNode);
        }
    });

    document.addEventListener("polymorphic.node.insert.after", function (event) {
        initFields(event.target);
    });

    initFields(document);
}

function initFields(base) {
    [...base.querySelectorAll('input[type=text][data-fallback-lang],textarea[data-fallback-lang]')].forEach(function (translatableInput) {
        configureTranslatableInput(translatableInput);
    });
}

function configureTranslatableInput(translatableInput) {
    const translatableInputName = translatableInput.getAttribute('name');
    const translatableInputLang = translatableInput.getAttribute('data-input-lang');

    const sourceInputLang = translatableInput.getAttribute('data-fallback-lang');
    const sourceInputName = translatableInputName.replace(`[${translatableInputLang}]`, `[${sourceInputLang}]`);

    let buttonAttributes = 'data-translate ';
    buttonAttributes += `data-source-field="${sourceInputName}" `;
    buttonAttributes += `data-target-field="${translatableInputName}" `;
    buttonAttributes += `data-source-locale="${sourceInputLang}" `;
    buttonAttributes += `data-target-locale="${translatableInputLang}" `;


    let translationButton;
    if (sfsCmsTranslationApiDriver === 'google') {
        // todo download google translate logo and use it from assets
        translationButton = '  <div class="input-group-prepend">\n' +
            '    <button class="p-2 text-primary bg-white border border-1" type="button" '+buttonAttributes+'>' +
            '<img src="https://upload.wikimedia.org/wikipedia/commons/d/d7/Google_Translate_logo.svg" width="16" height="18"/></button>\n' +
            '  </div>\n';
    } else {
        translationButton = '  <div class="input-group-prepend">\n' +
            '    <button class="p-2 text-primary bg-white border border-1" type="button" '+buttonAttributes+'><i class="bi bi-translate"></i></button>\n' +
            '  </div>\n';
    }

    let inputGroup = translatableInput.closest('.input-group');
    if (inputGroup) {
        let translationButtonElement = document.createElement('div');
        inputGroup.insertBefore(translationButtonElement, translatableInput);
        translationButtonElement.outerHTML = translationButton;
    } else {
        inputGroup = document.createElement('div');
        translatableInput.parentNode.appendChild(inputGroup);
        inputGroup.outerHTML = '<div class="input-group mb-3">\n' + translationButton + '</div>';
        inputGroup = translatableInput.parentNode.querySelector('.input-group');
        inputGroup.appendChild(translatableInput);
    }
}

function doTranslation(translateButton) {
    const sourceField = document.querySelector('[name="' + translateButton.getAttribute('data-source-field') + '"]');
    const targetField = document.querySelector('[name="' + translateButton.getAttribute('data-target-field') + '"]');
    const sourceLocale = translateButton.getAttribute('data-source-locale');
    const targetLocale = translateButton.getAttribute('data-target-locale');

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
                console.log('TRANSLATION ERROR', data);
                targetField.classList.add('sfs-cms-translation-error');
            }
        } catch (e) {
            console.error('Error parsing translation response', e);
            targetField.classList.add('sfs-cms-translation-error');
        }
    };
    xhr.open('GET', `${translateUrl}?text=${sourceField.value}&target=${targetLocale}&source=${sourceLocale}`);
    xhr.send();
}