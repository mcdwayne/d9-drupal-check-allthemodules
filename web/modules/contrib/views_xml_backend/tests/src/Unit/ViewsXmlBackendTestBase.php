<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase.
 */

namespace Drupal\Tests\views_xml_backend\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\pager\None;
use Drupal\views\Plugin\views\style\DefaultStyle;
use Drupal\views\ViewExecutable;

/**
 * Base testing class.
 */
abstract class ViewsXmlBackendTestBase extends UnitTestCase {

  protected $display;

  protected $view;

  public function setUp() {
    parent::setUp();

    if (!defined('REQUEST_TIME')) {
      define('REQUEST_TIME', time());
    }

    require_once dirname(dirname(dirname(__DIR__))) . '/views_xml_backend.module';
  }

  protected function getMockedView() {
    if (isset($this->view)) {
      return $this->view->reveal();
    }

    $this->view = $this->prophesize(ViewExecutable::class);
    $this->view->display_handler = $this->getMockedDisplay();
    $this->view->getDisplay()->willReturn($this->getMockedDisplay());
    $this->view->initPager()->willReturn(TRUE);
    $this->view->getStyle()->willReturn(new DefaultStyle([], '', []));

    $this->view->pager = new None([], '', []);
    $this->view->field = [];

    return $this->view->reveal();
  }

  protected function getMockedDisplay() {
    if (isset($this->display)) {
      return $this->display->reveal();
    }

    $this->display = $this->prophesize(DisplayPluginBase::class);
    $this->display->getArgumentsTokens()->willReturn([]);
    $this->display->getOption('relationships')->willReturn(FALSE);

    return $this->display->reveal();
  }

}
