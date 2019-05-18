<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

abstract class InsertImageCKEditorTestBase extends InsertImageTestBase {

  /**
   * @inheritdoc
   */
  public static $modules = [
    'node', 'file', 'image', 'insert', 'editor', 'field_ui', 'ckeditor'
  ];

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();

    // Create text format and associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 0,
      'filters' => [
        'filter_align' => [
          'status' => 1,
        ],
        'filter_caption' => [
          'status' => 1,
        ],
        'editor_file_reference' => [
          'status' => 1,
        ],
      ],
    ]);
    $filtered_html_format->save();
    $editor = Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
  }
}
