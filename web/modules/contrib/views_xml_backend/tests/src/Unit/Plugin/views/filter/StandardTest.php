<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\filter\StandardTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\filter;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\filter\Standard;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\filter\Standard
 * @group views_xml_backend
 */
class StandardTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::__toString
   */
  public function testToString() {
    $plugin = new Standard([], '', []);

    $options = ['xpath_selector' => 'xpath_query'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);

    $plugin->operator = '=';
    $plugin->value = 'foo';
    $this->assertSame("xpath_query = 'foo'", (string) $plugin);

    $plugin->operator = '!=';
    $this->assertSame("xpath_query != 'foo'", (string) $plugin);

    $plugin->operator = 'contains';
    $this->assertSame("contains(xpath_query, 'foo')", (string) $plugin);

    $plugin->operator = '!contains';
    $this->assertSame("not(contains(xpath_query, 'foo'))", (string) $plugin);
  }

}
