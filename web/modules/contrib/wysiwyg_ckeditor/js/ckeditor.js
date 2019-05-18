(function ($, Drupal, drupalSettings, CKEDITOR) {

"use strict";

Drupal.editors.ckeditor = {
  attach: function (element, format) {
    // Register additional Drupal plugins as necessary.
    if (format.editorSettings.externalPlugins) {
      for (var pluginName in format.editorSettings.externalPlugins) {
        if (format.editorSettings.externalPlugins.hasOwnProperty(pluginName)) {
          CKEDITOR.plugins.addExternal(pluginName, drupalSettings.basePath + format.editorSettings.externalPlugins[pluginName]['path'] + '/', format.editorSettings.externalPlugins[pluginName]['file']);
        }
      }
      delete format.editorSettings.externalPlugins;
    }
    return !!CKEDITOR.replace(element, format.editorSettings);
  },
  detach: function (element, format, trigger) {
    var editor = CKEDITOR.dom.element.get(element).getEditor();
    if (editor) {
      if (trigger === 'serialize') {
        editor.updateElement();
      }
      else {
        editor.destroy();
      }
    }
    return !!editor;
  }
};

})(jQuery, Drupal, drupalSettings, CKEDITOR);
