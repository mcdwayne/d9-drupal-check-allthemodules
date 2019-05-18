(function(QUnit, $, Drupal, CKEDITOR) {

  QUnit.module('Inserter', {
    afterEach: function() {
      $.each(CKEDITOR.instances, function(id, editor) {
        var element = editor.element;
        editor.destroy();
        element.$.remove();
      });

      CKEDITOR.currentInstance = undefined;

      $('.insert-test').remove();
    }
  });

  var editorInterface = Drupal.insert.editors.interfaces.CKEditor;
  var focusManager = new Drupal.insert.FocusManager(editorInterface);

  var $dom = $('<div class="insert-test" data-insert-type="test">\
    <input class="insert-style" type="hidden" value="test">\
    <div class="insert-templates">\
    <input class="insert-template" type="hidden" name="insert-template[test]" value="<span attr=&quot;__unused__&quot;>__filename__</span>">\
    </div>\
    </div>\
    ').appendTo('body');

  QUnit.test('getFocusManager()', function(assert) {
    var inserter = new Drupal.insert.Inserter(
      $dom.get(0),
      focusManager,
      editorInterface
    );

    assert.strictEqual(inserter.getFocusManager(), focusManager, 'Verified returning FocusManager instance.');
  });

  QUnit.test('getEditorInterface()', function(assert) {
    var inserter = new Drupal.insert.Inserter(
      $dom.get(0),
      focusManager,
      editorInterface
    );

    assert.strictEqual(inserter.getEditorInterface(), editorInterface, 'Verified returning EditorInterface instance.');
  });

  QUnit.test('getType()', function(assert) {
    var inserter = new Drupal.insert.Inserter(
      $dom.get(0),
      focusManager,
      editorInterface
    );

    assert.strictEqual(inserter.getType(), 'test', 'Verified returning insert type.');
  });

  QUnit.test('getTemplate()', function(assert) {
    var inserter = new Drupal.insert.Inserter(
      $dom.get(0),
      focusManager,
      editorInterface
    );

    assert.strictEqual(inserter.getTemplate(), $dom.find('[name="insert-template[test]"]').val(), 'Verified returning template.');
  });

})(QUnit, jQuery, Drupal, CKEDITOR);
