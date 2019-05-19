<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\filter\NumericTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\filter;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\filter\Numeric;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\filter\Numeric
 * @group views_xml_backend
 */
class NumericTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::__toString
   */
  public function testRenderItem() {
    $plugin = new Numeric([], '', []);

    $options = ['xpath_selector' => 'xpath_query'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);
    $plugin->operator = 'between';
    $plugin->value = ['min' => 1, 'max' => 10];

    $this->assertSame("xpath_query >= '1' and xpath_query <= '10'", (string) $plugin);

    $plugin->operator = 'not between';
    $this->assertSame("xpath_query <= '1' or xpath_query >= '10'", (string) $plugin);

    $plugin->operator = '=';
    $plugin->value['value'] = 5;
    $this->assertSame("xpath_query = '5'", (string) $plugin);
  }

}
