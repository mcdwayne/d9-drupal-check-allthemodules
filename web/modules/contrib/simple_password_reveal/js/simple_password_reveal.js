/**
 * @file
 * Toggles password field between password/text input field.
 */

(function () {
  var vanillaCheckbox = document.getElementById("edit-simple-password-reveal-checkbox");
  var vanillaPasswordFields = document.querySelectorAll("#edit-pass, #edit-current-pass, #edit-pass-pass1, #edit-pass-pass2");

  if (vanillaCheckbox !== null) {
    togglePasswordFields();

    vanillaCheckbox.addEventListener('change', function() {
      togglePasswordFields();
    });

    function togglePasswordFields() {
      [].forEach.call(vanillaPasswordFields, function(vanillaPasswordField) {
        if (vanillaCheckbox.checked) {
          vanillaPasswordField.type = 'password';
        }
        else {
          vanillaPasswordField.type = 'text';
        }
      });
    }
  }
})();
