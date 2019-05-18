(function ($) {
  'use strict';
  var credentialsButton = $('#generate-new-credentials-submit');
  var modal = $('#consentModal');
  var close = $('#consentModal .close, #consentModal #cancel');

  // When the user clicks on (x), close the modal.
  $.each(close, function (e) {
    $(this).click(function () {
      modal.css('display', 'none');
    });
  });
  // Lock Submit.
  credentialsButton.submit();

  credentialsButton.click(function () {
    modal.css('display', 'block');
  });
}(jQuery));
