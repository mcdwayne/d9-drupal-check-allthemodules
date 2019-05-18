<?php

namespace Drupal\Tests\commerce_xero\Unit\Annotation;

use Drupal\commerce_xero\Annotation\CommerceXeroProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the commerce xero annotation class.
 *
 * @group commerce_xero
 */
class CommerceXeroProcessorTest extends UnitTestCase {

  /**
   * Asserts that plugin class returns settings and ID.
   */
  public function testGet() {
    $expected = [
      'id' => 'commerce_xero_contact_processor',
      'settings' => ['blah' => 'blah'],
    ];

    $annotation = new CommerceXeroProcessor($expected);

    $this->assertEquals($expected, $annotation->get());
  }

}
