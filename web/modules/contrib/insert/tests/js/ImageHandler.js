(function(QUnit, $, Drupal, CKEDITOR) {

  QUnit.module('ImageHandler', {
    afterEach: function() {
      $.each(CKEDITOR.instances, function(id, editor) {
        var element = editor.element;
        editor.destroy();
        element.$.remove();
      });

      CKEDITOR.currentInstance = undefined;
    }
  });

  var $baseDom = $('<div class="insert-test-wrapper">\
    <input class="insert-filename" value="test-filename">\
    <input class="insert-title" name="field-name[title]" value="test-title">\
    <div class="insert-test">\
    <input class="insert-style" value="test">\
    <div class="insert-templates">\
    </div>\
    <div class="insert-button" data-insert-id="test-id"></div>\
    </div>\
    </div>\
    ');

  var editorInterface = Drupal.insert.editors.interfaces.CKEditor;
  var focusManager = new Drupal.insert.FocusManager(editorInterface);

  /**
   * @param {string} template
   * @param {Object} [settings]
   * @return {Drupal.insert.ImageHandler}
   */
  function instantiateImageHandler(template, settings) {
    var $dom = $baseDom.clone();
    $dom.find('.insert-templates').append(
      $('<input class="insert-template" name="insert-template[test]" value="' + template + '">')
    );
    var inserter = new Drupal.insert.Inserter($dom.find('.insert-test').get(0), focusManager, editorInterface);
    return new Drupal.insert.ImageHandler(inserter, settings);
  }

  /**
   * @param {Object} attach
   * @param {Object} [settings]
   * @return {Drupal.insert.ImageHandler}
   */
  function instantiateImageHandlerJson(attach, settings) {
    return instantiateImageHandler(''
      + '<img src=\'*\' data-insert-attach=\''
      + JSON.stringify(attach).replace(/"/g, '&quot;')
      + '\'>',
      settings
    );
  }

})(QUnit, jQuery, Drupal, CKEDITOR);
