(function (drupalSettings) {
  tarteaucitron.user.purechatId = drupalSettings.purechat.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('purechat');
})(drupalSettings);