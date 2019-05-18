<?php

namespace Drupal\Tests\range\Functional\Formatter;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\range\Traits\RangeTestTrait;

/**
 * Base class for testing formatter UI.
 */
abstract class RangeFormatterUiTestBase extends BrowserTestBase {

  use RangeTestTrait;

  /**
   * Field type to test against.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * Field name to test against.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'entity_test',
    'range',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test content',
      'administer entity_test display',
    ]));

    $this->fieldName = $this->getFieldName($this->fieldType);
    $this->createField($this->fieldType);
  }

}
