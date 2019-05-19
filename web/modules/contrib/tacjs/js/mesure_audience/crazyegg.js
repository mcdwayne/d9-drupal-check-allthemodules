(function (drupalSettings) {
  tarteaucitron.user.crazyeggId = drupalSettings.crazyegg.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('crazyegg');
})(drupalSettings);