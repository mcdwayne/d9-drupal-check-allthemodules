<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\argument\StandardTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\argument;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\argument\Standard;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\argument\Standard
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
    $plugin->argument = '"foo"';

    $this->assertSame('xpath_query = \'"foo"\'', (string) $plugin);
  }

}
