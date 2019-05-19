(function (drupalSettings) {
  tarteaucitron.user.webmecanikurl = drupalSettings.webmecanik.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('webmecanik');
})(drupalSettings);