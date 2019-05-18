<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;

abstract class InsertImageTestBase extends InsertFileTestBase {

  use ImageFieldCreationTrait {
    createImageField as drupalCreateImageField;
  }

  /**
   * @var array
   */
  public static $modules = [
    'node', 'file', 'image', 'insert', 'editor', 'field_ui'
  ];

  /**
   * @param string $name
   * @param array (optional) $field_settings
   */
  protected function createImageField($name, array $field_settings = []) {
    $this->drupalCreateImageField($name, $this->contentTypeName, [], $field_settings);
  }
}
