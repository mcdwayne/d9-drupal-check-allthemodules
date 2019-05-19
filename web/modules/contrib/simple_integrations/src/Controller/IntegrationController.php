<?php

namespace Drupal\simple_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_integrations\Entity\Integration;

/**
 * Basic Integration entity controller.
 */
class IntegrationController extends ControllerBase {

  /**
   * Return the label of an integration as the page title.
   *
   * @param \Drupal\simple_integrations\Entity\Integration $integration
   *   An integration entity.
   */
  public function getTitle(Integration $integration) {
    return $this->t('Edit %integration', [
      '%integration' => $integration->label,
    ]);
  }

}
