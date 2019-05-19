<?php

namespace Drupal\social_migration\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SocialMigrationController.
 */
class SocialMigrationController extends ControllerBase {

  /**
   * Route for social_migration.main.
   *
   * @return array
   *   The markup for the page.
   */
  public function main() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Social Migration Overview'),
    ];
  }

}
