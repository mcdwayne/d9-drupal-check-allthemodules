(function (drupalSettings) {
tarteaucitron.user.analyticsUa = drupalSettings.analytics.value;
tarteaucitron.user.analyticsMore = function () { /* add here your optionnal ga.push() */ };
(tarteaucitron.job = tarteaucitron.job || []).push('analytics');
})(drupalSettings);