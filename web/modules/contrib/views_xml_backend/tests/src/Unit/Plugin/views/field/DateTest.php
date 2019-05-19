<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\field\DateTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\field;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views_xml_backend\Plugin\views\field\Date;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\field\Date
 * @group views_xml_backend
 */
class DateTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::render_item
   */
  public function testRenderItem() {
    $date_formatter = $this->prophesize(DateFormatterInterface::class);
    $entity_storage = $this->prophesize(EntityStorageInterface::class);

    $date_formatter->formatTimeDiffSince(strtotime('January 1, 2000'), ['granularity' => 2])
      ->willReturn('1234')
      ->shouldBeCalled();

    $plugin = new Date([], '', [], $date_formatter->reveal(), $entity_storage->reveal());

    $plugin->field_alias = 'field_alias';

    $options = ['date_format' => 'raw time ago'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);

    $this->assertSame('1234', $plugin->render_item(0, ['value' => 'January 1, 2000']));
  }

}
