/**
 * @file
 * Plugin to insert abbreviation elements.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

(function ($) {
  // Register the plugin within the editor.
  CKEDITOR.plugins.add('abbr', {
    lang: 'en',

    // Register the icons.
    icons: 'abbr',

    // The plugin initialization logic goes inside this method.
    init: function (editor) {
      var lang = editor.lang.abbr;

      // Define an editor command that opens our dialog.
      editor.addCommand('abbr', new CKEDITOR.dialogCommand('abbrDialog', {

        // Allow abbr tag with optional title.
        allowedContent: 'abbr[title]',

        // Require abbr tag to be allowed to work.
        requiredContent: 'abbr',

        // Prefer abbr over acronym. Transform acronyms into abbrs.
        contentForms: [
                'abbr',
                'acronym'
        ]
      }));

      // Create a toolbar button that executes the above command.
      editor.ui.addButton('abbr', {

        // The text part of the button (if available) and tooptip.
        label: lang.buttonTitle,

        // The command to execute on click.
        command: 'abbr',

        // The button placement in the toolbar (toolbar group name).
        toolbar: 'insert',

        // The path to the icon.
        icon: this.path + 'icons/abbr.png'
      });

      if (editor.contextMenu) {
        editor.addMenuGroup('abbrGroup');
        editor.addMenuItem('abbrItem', {
          label: lang.menuItemTitle,
          icon: this.path + 'icons/abbr.png',
          command: 'abbr',
          group: 'abbrGroup'
        });

        editor.contextMenu.addListener(function (element) {
          if (element.getAscendant('abbr', true)) {
            return { abbrItem: CKEDITOR.TRISTATE_OFF };
          }
        });
      }

      // Register our dialog file. this.path is the plugin folder path.
      CKEDITOR.dialog.add('abbrDialog', this.path + 'dialogs/abbr.js');
    }
  });
})(jQuery);
