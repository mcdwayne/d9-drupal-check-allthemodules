(function (drupalSettings) {
tarteaucitron.user.etracker = drupalSettings.etracker.value;
(tarteaucitron.job = tarteaucitron.job || []).push('etracker');
})(drupalSettings);