<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\argument\NumericTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\argument;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\argument\Numeric;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\argument\Numeric
 * @group views_xml_backend
 */
class NumericTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::__toString
   */
  public function testToString() {
    $plugin = new Numeric([], '', []);

    $options = ['xpath_selector' => 'xpath_query'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);
    $plugin->argument = 4;

    $this->assertSame("xpath_query = '4'", (string) $plugin);
  }

}
