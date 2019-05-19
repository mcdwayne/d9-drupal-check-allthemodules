<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase.
 */

namespace Drupal\Tests\views_xml_backend\Unit;

/**
 * Module function tests.
 *
 * @group views_xml_backend
 */
class ModuleFuncsTest extends ViewsXmlBackendTestBase {

  /**
   * Tests for views_xml_backend_date().
   */
  public function testVxbDateInt() {
    $this->assertSame(strtotime('March 9, 2005 08:14:15'), views_xml_backend_date('March 9, 2005 08:14:15', 'second'));
    $this->assertSame(strtotime('March 9, 2005 08:14:00'), views_xml_backend_date('March 9, 2005 08:14:15', 'minute'));
    $this->assertSame(strtotime('March 9, 2005 08:00:00'), views_xml_backend_date('March 9, 2005 08:14:15', 'hour'));
    $this->assertSame(strtotime('March 9, 2005'), views_xml_backend_date('March 9, 2005 08:14:15', 'day'));
    $this->assertSame(strtotime('March 2005'), views_xml_backend_date('March 9, 2005 08:14:15', 'month'));
    $this->assertSame(strtotime('January 2005'), views_xml_backend_date('March 9, 2005 08:14:15', 'year'));
    $this->assertSame(strtotime('January 1, 2005'), views_xml_backend_date('2005', 'year'));
  }

}
