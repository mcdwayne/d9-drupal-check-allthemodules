<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\sort\NumericTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\sort;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\query\Xml;
use Drupal\views_xml_backend\Plugin\views\sort\Numeric;
use Drupal\views_xml_backend\Sorter\NumericSorter;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\sort\Numeric
 * @group views_xml_backend
 */
class NumericTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::query
   */
  public function testRenderItem() {
    $plugin = new Numeric([], '', []);

    $options = ['id' => 'sorter_id', 'xpath_selector' => 'xpath_query'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);

    $query = $this->prophesize(Xml::class);
    $query->addField('sort_numeric_sorter_id', 'xpath_query')->shouldBeCalled();
    $query->addSort(Argument::type(NumericSorter::class))->shouldBeCalled();

    $plugin->query = $query->reveal();

    $plugin->query();
  }

}
