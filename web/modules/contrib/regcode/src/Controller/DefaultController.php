<?php /**
 * @file
 * Contains \Drupal\regcode\Controller\DefaultController.
 */

namespace Drupal\regcode\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the regcode module.
 */
class DefaultController extends ControllerBase {

  public function regcode_admin_list() {
    return [
      '#markup' => t('This page should be replaced by Views. If you are seeing this page, please check your Views configuration.'),
    ];
  }

}
