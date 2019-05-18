<?php

/**
 * @file
 * Contains hunter test class.
 */

namespace Drupal\hunter_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class HunterTestController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function home() {
    return view('news', array('name' => 'DrupalHunter'));
  }

  /**
   * {@inheritdoc}
   */
  public function test() {
    return 'test page';
  }

}
