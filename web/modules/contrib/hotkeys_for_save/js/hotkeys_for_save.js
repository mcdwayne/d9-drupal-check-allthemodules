/**
 * @file
 * JavaScript behaviors for 'Hotkeys for Save' module.
 *
 * If hotkeys Ctrl+S (Win) or Cmd+S (Mac) are pressed, we are trying to find
 * submit button whose 'id' attribute is equal to the value from the specified
 * list.
 * And if such button was found we click on it.
 * The list includes the possible values of 'id' attribute that 'Save' buttons
 * can have.
 *
 * If on the page exist two buttons: 'Save' and 'Save and continue' then we
 * click on the button with 'continue' action.
 * For example, if exist two buttons: 'Save & exit' and 'Continue & edit', then
 * we clicked on the 'Continue & edit' button.
 *
 * Also we prevent to opening browser's 'Save As' dialog.
 */

(function ($, Drupal) {
  Drupal.behaviors.hotkeysForSave = {
    attach: function(context) {
      // The flag for preventing repeated submit when the key is pressed and held.
      var clickAllowed = true;

      function clickOnSubmitButton() {
        // First we try to find 'Save and continue' buttons.
        var selector = [
          '#edit-save-continue',
          '#edit-next',
          '#edit-continue',
        ];
        var submitButton = document.querySelector(selector.join());
        // If 'Save and continue' buttons wasn't found then we find 'Save' buttons.
        if (submitButton === null) {
          selector = [
            '#edit-submit',
            '#edit-save',
            '#edit-actions-save',
            '#edit-return',
            '#edit-actions-submit',
            '#edit-delete-entities',
          ];
          submitButton = document.querySelector(selector.join());
        }
        if (submitButton !== null && clickAllowed) {
          submitButton.click();
          // Preventing repeated submit.
          clickAllowed = false;
        }
      }

      // Attach keyboard events listeners to the document.
      $(document, context).once().each(function() {
        $(this).keyup(function(e) {
          if (e.keyCode === 83) {
            clickAllowed = true;
          }
        });

        $(this).keydown(function(e) {
          if (e.keyCode === 83 && (e.ctrlKey || e.metaKey)) {
            // Prevent to opening browser's 'Save As' dialog.
            e.preventDefault();
            clickOnSubmitButton();
          }
        });
      });

      // ---- Some event handlers for CKEditor ----.
      function keydown(e) {
        if ((e.data.$.keyCode === 83) && (e.data.$.ctrlKey || e.data.$.metaKey)) {
          e.data.preventDefault();
          clickOnSubmitButton();
        }
      }

      function keyup(e) {
        if (e.data.$.keyCode === 83) {
          clickAllowed = true;
        }
      }

      function contentDom(e) {
        var editable = e.editor.editable();
        editable.attachListener(editable, 'keydown', keydown);
        editable.attachListener(editable, 'keyup', keyup);
      }

      function instanceCreated(e) {
        e.editor.on('contentDom', contentDom);
      }

      // If CKEditor is present on the page then also attach keyboard events listeners to it.
      // Because if it gains focus then the document will not capture the events.
      if (window.CKEDITOR) {
        window.CKEDITOR.on('instanceCreated', instanceCreated);
      }
    },
  };
})(jQuery, Drupal);
