(function (drupalSettings) {
tarteaucitron.user.gtagUa = drupalSettings.googleanalytics.value;
tarteaucitron.user.gtagMore = function () { /* add here your optionnal gtag() */ };
(tarteaucitron.job = tarteaucitron.job || []).push('gtag');
})(drupalSettings);