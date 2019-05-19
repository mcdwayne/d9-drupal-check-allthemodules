(function (drupalSettings) {
  tarteaucitron.user.zopimID = drupalSettings.zopim.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('zopim');
})(drupalSettings);