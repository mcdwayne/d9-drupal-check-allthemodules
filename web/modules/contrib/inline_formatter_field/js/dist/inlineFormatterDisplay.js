"use strict";

// JavaScript to load the Ace Editor in for the format setting
(function ($, Drupal, drupalSettings) {
  // Function for editor size adjustment.
  function editorSizeAdjustment(e) {
    e.preventDefault();

    if ($(".EditorWrapper.Open").length < 1) {
      $(".ButtonSize").removeClass("fa-window-maximize").addClass("fa-window-restore");
      $(".EditorWrapper").addClass("Open");
    } else {
      $(".ButtonSize").removeClass("fa-window-restore").addClass("fa-window-maximize");
      $(".EditorWrapper").removeClass("Open");
    } // Send the resize even so that Ace Editor will resize its height.


    window.dispatchEvent(new Event("resize"));
  } // Function for our Drupal behavior attach.


  function loadInlineFormatterDisplay(context) {
    // Foreach textarea open, create the build.
    $(".AceEditorTextarea", context).once("ace_editor").each(function () {
      if ($(".AceEditorTextarea").length) {
        // Ace Editor settings and set up.
        var editor = ace.edit("AceEditor");
        editor.setTheme("ace/theme/".concat(drupalSettings.inline_formatter_field.ace_editor.setting.theme));
        editor.getSession().setMode("ace/mode/".concat(drupalSettings.inline_formatter_field.ace_editor.setting.mode));
        editor.session.setUseWrapMode(drupalSettings.inline_formatter_field.ace_editor.setting.wrap);
        editor.setShowPrintMargin(drupalSettings.inline_formatter_field.ace_editor.setting.print_margin);
        editor.getSession().setValue($(".AceEditorTextarea").val(), -1);
        editor.getSession().on("change", function () {
          $(".AceEditorTextarea").val(editor.getSession().getValue());
        }); // Toggle when the editor size button is clicked.

        $(".ButtonSize").click(editorSizeAdjustment);
      } // When 'esc' key is pressed, exit the full screen mode.


      $(document).keyup(function (e) {
        if (e.key === "Escape" && $(".EditorWrapper.Open").length > 0) {
          editorSizeAdjustment(e);
        }
      });
    });
  }

  function aceFailed() {
    $(".AceEditorTextarea").css({
      display: "block"
    });
    $(".EditorWrapper").css({
      display: "none"
    });
  } // Define our Drupal behavior.


  Drupal.behaviors.ace_editor = {}; // Check if Ace Editor loaded first before assigning the attach function.

  if (typeof ace !== "undefined") {
    Drupal.behaviors.ace_editor.attach = loadInlineFormatterDisplay;
  } else {
    // Wait for Ace Editor to load then assign the attach function.
    var waitTime = 0;
    var waitForAce = setInterval(function () {
      if (typeof ace !== "undefined") {
        Drupal.behaviors.ace_editor.attach = loadInlineFormatterDisplay;
        Drupal.behaviors.ace_editor.attach();
        clearInterval(waitForAce);
      } // Manual timeout to just use the textfield.


      if (waitTime > 25) {
        Drupal.behaviors.ace_editor.attach = aceFailed;
        Drupal.behaviors.ace_editor.attach();
        clearInterval(waitForAce);
      }

      waitTime += 1;
    }, 150);
  }
})(jQuery, Drupal, drupalSettings);