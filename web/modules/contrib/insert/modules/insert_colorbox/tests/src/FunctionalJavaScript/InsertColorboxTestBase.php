<?php

namespace Drupal\Tests\insert_colorbox\FunctionalJavascript;

use Drupal\Tests\insert\FunctionalJavascript\InsertImageTestBase;

abstract class InsertColorboxTestBase extends InsertImageTestBase {

  /**
   * @var array
   */
  public static $modules = [
    'node', 'file', 'image', 'insert', 'insert_colorbox', 'field_ui'
  ];
}
