<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Tests\UnitTestCase;
use Drupal\xero\Plugin\DataType\LineItem;

/**
 * Test the xero_line_item type.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\LineItem
 * @group Xero
 */
class LineItemTest extends UnitTestCase {

  /**
   * Assert that static variables are present.
   */
  public function testStaticVariables() {
    $this->assertEquals('LineItem', LineItem::$xero_name);
    $this->assertEquals('LineItems', LineItem::$plural_name);
  }

}
