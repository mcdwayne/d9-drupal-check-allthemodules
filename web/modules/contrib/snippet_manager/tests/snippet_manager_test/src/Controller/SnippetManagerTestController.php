<?php

namespace Drupal\snippet_manager_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Snippet manager test routes.
 */
class SnippetManagerTestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    return [
      '#markup' => $this->t('Bar.'),
    ];
  }

}
