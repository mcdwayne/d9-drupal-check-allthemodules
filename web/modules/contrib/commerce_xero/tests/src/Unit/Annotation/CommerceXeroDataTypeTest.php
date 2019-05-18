<?php

namespace Drupal\Tests\commerce_xero\Unit\Annotation;

use Drupal\Tests\UnitTestCase;
use Drupal\commerce_xero\Annotation\CommerceXeroDataType;

/**
 * Tests the annotation.
 *
 * @group commerce_xero
 */
class CommerceXeroDataTypeTest extends UnitTestCase {

  /**
   * Asserts that plugin class returns settings and ID.
   */
  public function testGet() {
    $expected = [
      'id' => 'commerce_xero_bank_transaction',
      'label' => 'Bank Transaction',
      'settings' => [],
      'type' => 'xero_bank_transaction',
    ];

    $annotation = new CommerceXeroDataType($expected);

    $this->assertEquals($expected, $annotation->get());
  }

}
