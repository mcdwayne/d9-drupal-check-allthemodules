/**
 * @file
 * Defines Javascript behaviors for the Quick Script module.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.quickscript = {
    ctx: false,
    codeEditors: {}
  };

  /**
   * Updates the theme for the code editor.
   *
   * @param {string} theme
   *   Theme to use for the code editor.
   */
  Drupal.quickscript.updateTheme = function (theme) {
    $.each(this.codeEditors, function (i, editor) {
      editor.setOption('theme', theme);
    });
    window.localStorage.setItem('quickscript.theme', theme);
  };

  /**
   *
   * @param $textarea
   */
  Drupal.quickscript.initCodeEditor = function ($textarea, mode, theme) {
    return CodeMirror.fromTextArea($textarea.get(0), {
      lineNumbers: true,
      mode: mode,
      theme: theme,
      tabSize: 2,
      indentUnit: 2,
      extraKeys: { // Set TAB to make spaces.
        Tab: function (cm) {
          var spaces = Array(cm.getOption("indentUnit") + 1).join(" ");
          cm.replaceSelection(spaces, "end", "+input");
        }
      }
    });
  };

  /**
   * Prepends the example YAML text into the Form YAML textarea.
   */
  Drupal.quickscript.viewYamlExample = function () {
    Drupal.quickscript.codeEditors.yaml.setValue("### EXAMPLE START ###\n" +
      "# Customize the form by using the 'settings' key.\n" +
      "settings:\n" +
      "  form_title: 'Your form title'\n" +
      "  form_description: 'Your form description.'\n" +
      "  submit_value: 'Execute the Operation'\n" +
      "\n" +
      "# Create form elements using machine_name and attributes with underscores.\n" +
      "# Form submitted values are available in your script as $_QS['machine_name']\n" +
      "my_textfield_value:\n" +
      "  _title: 'Enter a Value'\n" +
      "  _type: textfield\n" +
      "  _default_value: ''\n" +
      "  _attributes: {placeholder: 'Type here...'}\n" +
      "\n" +
      "# Create nested form elements.\n" +
      "customize:\n" +
      "  _type: details\n" +
      "  _title: Customize\n" +
      "  setting_one:\n" +
      "    _type: select\n" +
      "    _title: 'Setting One'\n" +
      "    _description: 'This will be nested under the Customize wrapper.'\n" +
      "    _options:\n" +
      "      key1: 'Value 1'\n" +
      "      key2: 'Value 2'\n" +
      "\n" +
      "# Supports most Form API elements and attributes.\n" +
      "checkbox:\n" +
      "  _type: checkbox\n" +
      "  _title: 'Check to enable date field'\n" +
      "\n" +
      "# For example, you can use the States API to toggle showing elements.\n" +
      "date:\n" +
      "  _type: date\n" +
      "  _title: Date\n" +
      "  _states:\n" +
      "    visible:\n" +
      "      input[name=\"form[checkbox]\"]: {checked: true}\n" +
      "### EXAMPLE END ###\n" +
      "\n" + Drupal.quickscript.codeEditors.yaml.getValue());
  };

  Drupal.behaviors.quickscript = {
    attach: function (context) {
      Drupal.quickscript.ctx = $(context);

      var theme = 'default';

      if (!drupalSettings.quickscript.enable_code_editor) {
        return;
      }

      // Check if there is a theme to use.
      if (window.localStorage.getItem('quickscript.theme')) {
        $(context).find('select[name="theme_selector"]').val(window.localStorage.getItem('quickscript.theme'));
        theme = window.localStorage.getItem('quickscript.theme');
      }

      // Enable CodeMirror on preview textarea.
      if ($('.form-item-code-preview textarea', $(context)).length) {
        Drupal.quickscript.codeEditors.preview = Drupal.quickscript.initCodeEditor($('.form-item-code-preview textarea', $(context)), 'application/x-httpd-php', theme);
      }

      // Enable CodeMirror on the code field textarea.
      if ($('.field--name-code textarea', $(context)).length) {
        Drupal.quickscript.codeEditors.code = Drupal.quickscript.initCodeEditor($('.field--name-code textarea', $(context)), 'application/x-httpd-php', theme);
      }

      // If details is already open, enable CodeMirror instantly.
      if ($('details#edit-form', $(context)).attr('open') === 'open') {
        Drupal.quickscript.codeEditors.yaml = Drupal.quickscript.initCodeEditor($('.field--name-form-yaml textarea', $(context)), 'text/yaml', theme);
      }
      else {
        // Initiate CodeMirror when the user opens the details pane.
        $('details#edit-form', $(context)).on('toggle', function (e) {
          if (typeof Drupal.quickscript.codeEditors.yaml === 'undefined') {
            Drupal.quickscript.codeEditors.yaml = Drupal.quickscript.initCodeEditor($('.field--name-form-yaml textarea', $(context)), 'text/yaml', theme);
          }
        });
      }

      // Enable CodeMirror on YAML Form textarea.
      if ($('.view-yaml-example', $(context)).length) {
        $('.view-yaml-example', $(context)).on('click', function (e) {
          e.preventDefault();
          Drupal.quickscript.viewYamlExample($('.field--name-form-yaml textarea', $(context)));
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
