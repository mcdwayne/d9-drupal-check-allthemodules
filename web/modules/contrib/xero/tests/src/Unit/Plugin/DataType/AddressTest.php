<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Tests\UnitTestCase;
use Drupal\xero\Plugin\DataType\Address;

/**
 * Tests the xero_address type.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\Address
 * @group Xero
 */
class AddressTest extends UnitTestCase {

  /**
   * Assert that static variables are present.
   */
  public function testStaticVariables() {
    $this->assertEquals('Address', Address::$xero_name);
    $this->assertEquals('Addresses', Address::$plural_name);
  }

}
