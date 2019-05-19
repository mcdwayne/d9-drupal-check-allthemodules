(function (drupalSettings) {
  tarteaucitron.user.alexaAccountID = drupalSettings.alexa.value;
  (tarteaucitron.job = tarteaucitron.job || []).push('alexa');
})(drupalSettings);