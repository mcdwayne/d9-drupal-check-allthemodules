(function (drupalSettings) {
tarteaucitron.user.mauticurl = drupalSettings.mautic.value;
(tarteaucitron.job = tarteaucitron.job || []).push('mautic');
})(drupalSettings);