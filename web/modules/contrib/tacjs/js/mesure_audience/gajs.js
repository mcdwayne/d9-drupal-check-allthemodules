(function (drupalSettings) {
    tarteaucitron.user.gajsUa = drupalSettings.gajs.value;
    tarteaucitron.user.gajsMore = function () { /* add here your optionnal _ga.push() */ };
    (tarteaucitron.job = tarteaucitron.job || []).push('gajs');
})(drupalSettings);