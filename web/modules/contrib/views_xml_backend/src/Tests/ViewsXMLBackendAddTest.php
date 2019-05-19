<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Tests\ViewsXMLBackendAddTest.
 */

namespace Drupal\views_xml_backend\Tests;

/**
 * Tests basic functions from the Views XML Backend module.
 *
 * @group views_xml_backend
 */

class ViewsXMLBackendAddTest extends ViewsXMLBackendBase {

  /**
   * Tests Views XML Backend option appears in new View admin page.
   */
  public function testAddViewViewsXMLBackend() {
    $this->addXMLBackendView();
  }

  /**
   * Tests new Views XML Backend View can be created.
   */
  public function testAddMinimalViewViewsXMLBackend() {
    $this->addMinimalXMLBackendView();
  }

  /**
   * Tests Views XML Backend View can set Query Settings for
   * XML source and set existing/new XML fields.
   */
  public function testAddStandardViewsXMLBackend() {
    $this->addStandardXMLBackendView();
  }

}
