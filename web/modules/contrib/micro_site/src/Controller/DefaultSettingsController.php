<?php

namespace Drupal\micro_site\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DefaultSettingsController.
 */
class DefaultSettingsController extends ControllerBase {

  /**
   * Default page for various micro site settings.
   *
   * @return array
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Micro site settings'),
    ];
  }

}
