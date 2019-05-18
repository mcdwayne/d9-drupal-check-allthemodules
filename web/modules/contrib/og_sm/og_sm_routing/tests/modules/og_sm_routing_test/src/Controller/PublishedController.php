<?php

namespace Drupal\og_sm_routing_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the og_sm_routing_test.published site route.
 */
class PublishedController extends ControllerBase {

  /**
   * Prints a message indicating this site is published.
   *
   * @return array
   *   A render array containing the message.
   */
  public function published() {
    return [
      '#markup' => $this->t('You are looking at a published site!'),
    ];
  }

}
