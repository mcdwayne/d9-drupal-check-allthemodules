<?php

namespace Drupal\ad_choices_link\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for ad_choices_link routes.
 */
class AdChoicesLinkController extends ControllerBase {

  /**
   * Returns message saying JS needs to be enabled.
   *
   * @todo find a better way to deal with this.
   *
   * @return array
   *   A render array for the page.
   */
  public function noJs() {

    $build['content'] = [
      '#markup' => $this->t('This feature does not work without JavaScript being enabled.'),
    ];

    return $build;
  }

}
