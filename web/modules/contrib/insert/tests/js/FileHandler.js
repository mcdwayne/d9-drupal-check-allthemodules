(function(QUnit, $, Drupal, CKEDITOR) {

  QUnit.module('FileHandler', {
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
    <input class="insert-description" name="field-name[description]" value="test-description">\
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
   * @param {boolean} returnDom
   * @return {array|Drupal.insert.FileHandler}
   */
  function instantiateFileHandler(template, returnDom) {
    var $dom = $baseDom.clone();
    $dom.find('.insert-templates').append(
      $('<input class="insert-template" name="insert-template[test]" value="' + template + '">')
    );
    var inserter = new Drupal.insert.Inserter($dom.find('.insert-test').get(0), focusManager, editorInterface);
    var handler = new Drupal.insert.FileHandler(inserter);
    return returnDom ? [handler, $dom] : handler;
  }

  /**
   * @param {Object} attach
   * @param {boolean} returnDom
   * @return {array|Drupal.insert.FileHandler}
   */
  function instantiateFileHandlerJson(attach, returnDom) {
    return instantiateFileHandler(''
      + '<span data-insert-attach=\''
      + JSON.stringify(attach).replace(/"/g, '&quot;')
      + '\'>test</span>',
      returnDom
    );
  }

  QUnit.test('buildContent(): no attachments', function(assert) {
    var fileHandler = instantiateFileHandler('<span>test</span>');
    assert.strictEqual(fileHandler.buildContent(), '<span>test</span>', 'Verified result: ' + fileHandler.buildContent());

    fileHandler = instantiateFileHandler('<span class=&quot;test&quot;>test</span>');
    assert.strictEqual(fileHandler.buildContent(), '<span class="test">test</span>', 'Verified result: ' + fileHandler.buildContent());
  });

  QUnit.test('buildContent(): attribute attachments', function(assert) {
    var fileHandler = instantiateFileHandlerJson({
        "attributes": {
          "class": ["description"]
        }
      });

    var $content = $(fileHandler.buildContent());

    assert.strictEqual($content.attr('class'), 'test-description', 'Verified setting attribute: ' + fileHandler.buildContent());

    fileHandler = instantiateFileHandlerJson({
        "attributes": {
          "class": ["does-not-exist", "description"]
        }
      }
    );

    $content = $(fileHandler.buildContent());

    assert.strictEqual($content.attr('class'), 'test-description', 'Verified setting fallback attribute: ' + fileHandler.buildContent());

    fileHandler = instantiateFileHandlerJson({
        "attributes": {
          "class": ["filename", "description"]
        }
      });

    $content = $(fileHandler.buildContent());

    assert.strictEqual($content.attr('class'), 'test-filename', 'Verified not setting fallback attribute when first value is not empty: ' + fileHandler.buildContent());

    fileHandler = instantiateFileHandlerJson({
        "attributes": {
          "class": ["filename", "description"],
          "title": ["does-not-exist", "filename"]
        }
      });

    $content = $(fileHandler.buildContent());

    assert.strictEqual($content.attr('class') + '_' + $content.attr('title'), 'test-filename_test-filename', 'Verified setting two attributes: ' + fileHandler.buildContent());
  });

  QUnit.test('buildContent(): content attachments', function(assert) {
    var fileHandler = instantiateFileHandlerJson({
        "content": ["description"]
      });

    var $content = $(fileHandler.buildContent());

    assert.strictEqual($content.text(), 'test-description', 'Verified setting content: ' + fileHandler.buildContent());

    fileHandler = instantiateFileHandlerJson({
        "content": ["does-not-exist", "description"]
      });

    $content = $(fileHandler.buildContent());

    assert.strictEqual($content.text(), 'test-description', 'Verified setting fallback content: ' + fileHandler.buildContent());

    fileHandler = instantiateFileHandlerJson({
        "content": ["filename", "description"]
      });

    $content = $(fileHandler.buildContent());

    assert.strictEqual($content.text(), 'test-filename', 'Verified not setting fallback content when first value is not empty: ' + fileHandler.buildContent());
  });

  QUnit.test('Update attribute in textarea', function(assert) {

    var $textarea = $('<textarea>');

    focusManager.addTextareas($textarea);

    var fileHandler = instantiateFileHandlerJson({
      "id": "test-id",
      "attributes": {
        "class": ["description"]
      }
    });

    $textarea.val(fileHandler.buildContent());

    var $dom = fileHandler._inserter.$container.parent();

    var $description = $dom.find('.insert-description');
    $description.val('overwritten');
    $description.trigger('input');

    assert.strictEqual($($textarea.val()).attr('class'), 'overwritten', 'Updated attribute: ' + $textarea.val());
  });

  QUnit.test('Update attributes of multiple instances in textarea', function(assert) {

    var $textarea = $('<textarea>');

    focusManager.addTextareas($textarea);

    var fileHandler = instantiateFileHandlerJson({
      "id": "test-id",
      "attributes": {
        "class": ["description"]
      }
    });

    $textarea.val(fileHandler.buildContent() + fileHandler.buildContent());

    var $dom = fileHandler._inserter.$container.parent();

    var $description = $dom.find('.insert-description');
    $description.val('overwritten');
    $description.trigger('input');

    assert.strictEqual(
      $($textarea.val()).eq(0).attr('class') + '_' +  $($textarea.val()).eq(1).attr('class'),
      'overwritten_overwritten',
      'Updated attribute: ' + $textarea.val()
    );
  });

  QUnit.test('Update content in textarea', function(assert) {

    var $textarea = $('<textarea>');

    focusManager.addTextareas($textarea);

    var fileHandler = instantiateFileHandlerJson({
      "id": "test-id",
      "content": ["description"]
    });

    $textarea.val(fileHandler.buildContent());

    var $dom = fileHandler._inserter.$container.parent();

    var $description = $dom.find('.insert-description');
    $description.val('overwritten');
    $description.trigger('input');

    assert.strictEqual($($textarea.val()).text(), 'overwritten', 'Updated content: ' + $textarea.val());
  });

  QUnit.test('Update content of multiple instances in textarea', function(assert) {

    var $textarea = $('<textarea>');

    focusManager.addTextareas($textarea);

    var fileHandler = instantiateFileHandlerJson({
      "id": "test-id",
      "content": ["description"]
    });

    $textarea.val(fileHandler.buildContent() + fileHandler.buildContent());

    var $dom = fileHandler._inserter.$container.parent();

    var $description = $dom.find('.insert-description');
    $description.val('overwritten');
    $description.trigger('input');

    assert.strictEqual(
      $($textarea.val()).eq(0).text() + '_' + $($textarea.val()).eq(1).text(),
      'overwritten_overwritten',
      'Updated content: ' + $textarea.val()
    );
  });

  QUnit.test('Update attribute in CKEditor instance', function(assert) {
    var done = assert.async();

    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    editor.on('contentDom', function() {
      focusManager.addEditor(editor);

      var instantiation = instantiateFileHandlerJson({
        "id": "test-id",
        "attributes": {
          "class": ["description"]
        }
      }, true);
      var fileHandler = instantiation[0];
      var $dom = instantiation[1];

      $(editor.document.$).find('body').empty().append(fileHandler.buildContent());

      var $description = $dom.find('.insert-description');
      $description.val('overwritten');
      $description.trigger('input');

      var $alteredDom = $(editor.document.$).find('body').children();

      assert.strictEqual($alteredDom.attr('class'), 'overwritten', 'Updated attribute: ' + $(editor.document.$).find('body').html());

      done();
    });
  });

  QUnit.test('Update attributes of multiple instances in CKEditor instance', function(assert) {
    var done = assert.async();

    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    editor.on('contentDom', function() {
      focusManager.addEditor(editor);

      var instantiation = instantiateFileHandlerJson({
        "id": "test-id",
        "attributes": {
          "class": ["description"]
        }
      }, true);
      var fileHandler = instantiation[0];
      var $dom = instantiation[1];

      $(editor.document.$).find('body').empty()
        .append(fileHandler.buildContent() + fileHandler.buildContent());

      var $description = $dom.find('.insert-description');
      $description.val('overwritten');
      $description.trigger('input');

      var $alteredDom = $(editor.document.$).find('body').children();

      assert.strictEqual(
        $alteredDom.eq(0).attr('class') + '_' + $alteredDom.eq(1).attr('class'),
        'overwritten_overwritten',
        'Updated attribute: ' + $(editor.document.$).find('body').html()
      );

      done();
    });
  });

  QUnit.test('Update content in CKEditor instance', function(assert) {
    var done = assert.async();

    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    editor.on('contentDom', function() {
      focusManager.addEditor(editor);

      var instantiation = instantiateFileHandlerJson({
        "id": "test-id",
        "content": ["description"]
      }, true);
      var fileHandler = instantiation[0];
      var $dom = instantiation[1];

      $(editor.document.$).find('body').empty().append(fileHandler.buildContent());

      var $description = $dom.find('.insert-description');
      $description.val('overwritten');
      $description.trigger('input');

      var $alteredDom = $(editor.document.$).find('body').children();

      assert.strictEqual($alteredDom.text(), 'overwritten', 'Updated content: ' + $(editor.document.$).find('body').html());

      done();
    });
  });

  QUnit.test('Update content of multiple instances in CKEditor instance', function(assert) {
    var done = assert.async();

    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    editor.on('contentDom', function() {
      focusManager.addEditor(editor);

      var instantiation = instantiateFileHandlerJson({
        "id": "test-id",
        "content": ["description"]
      }, true);
      var fileHandler = instantiation[0];
      var $dom = instantiation[1];

      $(editor.document.$).find('body').empty()
        .append(fileHandler.buildContent() + fileHandler.buildContent());

      var $description = $dom.find('.insert-description');
      $description.val('overwritten');
      $description.trigger('input');

      var $alteredDom = $(editor.document.$).find('body').children();

      assert.strictEqual(
        $alteredDom.eq(0).text() + '_' + $alteredDom.eq(1).text(),
        'overwritten_overwritten',
        'Updated content: ' + $(editor.document.$).find('body').html()
      );

      done();
    });
  });

  QUnit.test('Update attributes in textarea and CKEditor instance', function(assert) {
    var done = assert.async();

    var $textarea = $('<textarea>');
    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    focusManager.addTextareas($textarea);

    editor.on('contentDom', function() {
      focusManager.addEditor(editor);

      var instantiation = instantiateFileHandlerJson({
        "id": "test-id",
        "attributes": {
          "class": ["description"]
        }
      }, true);
      var fileHandler = instantiation[0];
      var $dom = instantiation[1];

      $textarea.val(fileHandler.buildContent());
      $(editor.document.$).find('body').empty().append(fileHandler.buildContent());

      var $description = $dom.find('.insert-description');
      $description.val('overwritten');
      $description.trigger('input');

      var $alteredDom = $(editor.document.$).find('body').children();

      assert.strictEqual(
        $alteredDom.attr('class'),
        'overwritten',
        'Updated editor attribute: ' + $(editor.document.$).find('body').html()
      );

      assert.strictEqual(
        $($textarea.val()).attr('class'),
        'overwritten',
        'Updated textarea attribute: ' + $textarea.val()
      );

      done();

    });
  });

  QUnit.test('Update content in textarea and CKEditor instance', function(assert) {
    var done = assert.async();

    var $textarea = $('<textarea>');
    var $editor = $('<div>').appendTo('body');
    var editor = CKEDITOR.replace($editor.get(0));

    focusManager.addTextareas($textarea);

    editor.on('contentDom', function() {
      focusManager.addEditor(editor);

      var instantiation = instantiateFileHandlerJson({
        "id": "test-id",
        "content": ["description"]
      }, true);
      var fileHandler = instantiation[0];
      var $dom = instantiation[1];

      $textarea.val(fileHandler.buildContent());
      $(editor.document.$).find('body').empty().append(fileHandler.buildContent());

      var $description = $dom.find('.insert-description');
      $description.val('overwritten');
      $description.trigger('input');

      var $alteredDom = $(editor.document.$).find('body').children();

      assert.strictEqual(
        $alteredDom.text(),
        'overwritten',
        'Updated editor attribute: ' + $(editor.document.$).find('body').html()
      );

      assert.strictEqual(
        $($textarea.val()).text(),
        'overwritten',
        'Updated textarea attribute: ' + $textarea.val()
      );

      done();

    });
  });

})(QUnit, jQuery, Drupal, CKEDITOR);
