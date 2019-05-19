
(function (drupalSettings) {
  tarteaucitron.user.multiplegtagUa = drupalSettings.gtagmultiple.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('multiplegtag');
})(drupalSettings);