(function (drupalSettings) {
tarteaucitron.user.facebookpixelId = drupalSettings.facebookpixelId.value;
tarteaucitron.user.facebookpixelMore = function () {
  /* add here your optionnal facebook pixel function */ };
(tarteaucitron.job = tarteaucitron.job || []).push('facebookpixel');
})(drupalSettings);