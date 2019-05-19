(function (drupalSettings) {
tarteaucitron.user.clickyId = drupalSettings.clicky.value;
tarteaucitron.user.clickyMore = function () { /* add here your optionnal clicky function */ };
(tarteaucitron.job = tarteaucitron.job || []).push('clicky');
})(drupalSettings);