(function (drupalSettings) {
tarteaucitron.user.googletagmanagerId = drupalSettings.google_tag_manager.value;
(tarteaucitron.job = tarteaucitron.job || []).push('googletagmanager');
})(drupalSettings);