<?php

/**
 * @file
 * Contains \Drupal\packery\Controller.
 */

namespace Drupal\packery\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a class to list settings groups.
 *
 * @see \Drupal\user\Entity\Group
 */
class PackeryController extends ControllerBase {

  /**
   * Settings group list.
   */
  public function groupOverview() {
    $form = \Drupal::formBuilder()->getForm('Drupal\packery\Form\PackeryGroupDisplayForm');
    return $form;
  }
}
