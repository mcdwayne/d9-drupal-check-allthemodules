<?php

namespace Drupal\payex\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a route controller for PayEx routes.
 */
class PayExController extends ControllerBase {

  /**
   * Displays the form to add a new PayExSetting entity
   *
   * @return array
   *   The render array for the page.
   */
  public function addPayExSetting() {
    $entity = $this->entityTypeManager()->getStorage('payex_setting')->create();
    return $this->entityFormBuilder()->getForm($entity);
  }

}
