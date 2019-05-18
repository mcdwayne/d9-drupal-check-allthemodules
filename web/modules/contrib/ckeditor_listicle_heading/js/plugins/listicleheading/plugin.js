/**
 * @file
 * Plugin to insert listicle heading elements.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

(function ($) {
  // Register the plugin within the editor.
  CKEDITOR.plugins.add('listicleheading', {
    lang: 'en',

    // Register the icons.
    icons: 'listicleheading',

    // The plugin initialization logic goes inside this method.
    init: function (editor) {
      var lang = editor.lang.listicleheading;

      // Define an editor command that opens our dialog.
      editor.addCommand('listicleheading', new CKEDITOR.dialogCommand('listicleheadingDialog'));

      // Create a toolbar button that executes the above command.
      editor.ui.addButton('listicleheading', {

        // The text part of the button (if available) and tooptip.
        label: lang.buttonTitle,

        // The command to execute on click.
        command: 'listicleheading',

        // The button placement in the toolbar (toolbar group name).
        toolbar: 'insert',

        // The path to the icon.
        icon: this.path + 'icons/listicleheading.png'
      });

      if (editor.contextMenu) {
        editor.addMenuGroup('listicleheadingGroup');
        editor.addMenuItem('listicleheadingItem', {
          label: lang.menuItemTitle,
          icon: this.path + 'icons/listicleheading.png',
          command: 'listicleheading',
          group: 'listicleheadingGroup'
        });

        editor.contextMenu.addListener(function (element) {
          if (element.getAscendant('div', true)) {
            return { div: CKEDITOR.TRISTATE_OFF };
          }
        });
      }

      // Register our dialog file. this.path is the plugin folder path.
      CKEDITOR.dialog.add('listicleheadingDialog', this.path + 'dialogs/listicleheading.js');
    }
  });
})(jQuery);
