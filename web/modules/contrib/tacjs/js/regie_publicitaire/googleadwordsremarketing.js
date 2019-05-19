(function (drupalSettings) {
  tarteaucitron.user.adwordsremarketingId = drupalSettings.googleadwordsremarketing.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('googleadwordsremarketing');
})(drupalSettings);