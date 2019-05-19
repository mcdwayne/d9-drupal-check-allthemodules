(function (drupalSettings) {
  tarteaucitron.user.xitiId = drupalSettings.xiti.value;
  tarteaucitron.user.xitiMore = function () { /* add here your optionnal xiti function */ };
  (tarteaucitron.job = tarteaucitron.job || []).push('xiti');
})(drupalSettings);