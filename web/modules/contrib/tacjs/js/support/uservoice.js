(function (drupalSettings) {
  tarteaucitron.user.userVoiceApi = drupalSettings.uservoice.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('uservoice');
})(drupalSettings);