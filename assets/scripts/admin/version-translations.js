(function () {
    if (!window.__sfs_version_translations_registered) {
        window.addEventListener('load', _init);
    }
    window.__sfs_version_translations_registered = true;
})();


function _init() {
    const translateButtons = document.querySelectorAll('[data-locale-trans-toggler]')
    translateButtons.forEach(function (translateButton) {
        translateButton.addEventListener('click', function (event) {
            togglerTranslations(translateButton);
        });
    });
}

function togglerTranslations(translateButton) {
    const targetLocale = translateButton.getAttribute('data-locale-trans-toggler');
    document.querySelectorAll('[data-locale-trans]').forEach(function (row) {
        const locale = row.getAttribute('data-locale-trans');

        if(locale == targetLocale) {
            row.classList.remove('d-none');
            row.classList.remove('hidden');
        } else {
            row.classList.add('d-none');
            row.classList.add('hidden');
        }
    });
}
