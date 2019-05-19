<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\sort\StandardTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\sort;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\query\Xml;
use Drupal\views_xml_backend\Plugin\views\sort\Standard;
use Drupal\views_xml_backend\Sorter\StringSorter;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\sort\Standard
 * @group views_xml_backend
 */
class StandardTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::query
   */
  public function testRenderItem() {
    $plugin = new Standard([], '', []);

    $options = ['id' => 'sorter_id', 'xpath_selector' => 'xpath_query'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);

    $query = $this->prophesize(Xml::class);
    $query->addField('sort_string_sorter_id', 'xpath_query')->shouldBeCalled();
    $query->addSort(Argument::type(StringSorter::class))->shouldBeCalled();

    $plugin->query = $query->reveal();

    $plugin->query();
  }

}
