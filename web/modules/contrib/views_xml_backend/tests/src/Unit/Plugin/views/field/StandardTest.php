<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\field\StandardTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\field;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\field\Standard;
use Drupal\views_xml_backend\Plugin\views\query\Xml;
use Drupal\views_xml_backend\Sorter\StringSorter;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\field\Standard
 * @group views_xml_backend
 */
class StandardTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::clickSort
   */
  public function testClickSort() {
    $plugin = new Standard([], '', []);

    $query = $this->prophesize(Xml::class);
    $query->addSort(Argument::type(StringSorter::class))->shouldBeCalled();

    $plugin->query = $query->reveal();

    $plugin->clickSort('DESC');
  }

}
