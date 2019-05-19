(function (drupalSettings) {
  tarteaucitron.user.disqusShortname = drupalSettings.disqus.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('disqus');
})(drupalSettings);