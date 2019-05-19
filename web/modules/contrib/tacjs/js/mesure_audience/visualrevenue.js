(function (drupalSettings) {
  tarteaucitron.user.visualrevenueId = drupalSettings.visualrevenue.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('visualrevenue');
})(drupalSettings);