(function (drupalSettings) {
tarteaucitron.user.kobanurl = drupalSettings.koban_api.value;
tarteaucitron.user.kobanapi = drupalSettings.Koben_url.value;
(tarteaucitron.job = tarteaucitron.job || []).push('koban');
})(drupalSettings);