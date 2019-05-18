<?php /**
 * @file
 * Contains \Drupal\shorten_cs\Controller\DefaultController.
 */

namespace Drupal\shorten_cs\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the shorten_cs module.
 */
class DefaultController extends ControllerBase {

  public function shorten_cs_edit_form($service) {
    $form = \Drupal::formBuilder()->getForm('shorten_cs_edit', $service);
    return \Drupal::service("renderer")->render($form);
  }

  public function shorten_cs_delete_form($service) {
    $form = \Drupal::formBuilder()->getForm('shorten_cs_delete', $service);
    return \Drupal::service("renderer")->render($form);
  }

}
