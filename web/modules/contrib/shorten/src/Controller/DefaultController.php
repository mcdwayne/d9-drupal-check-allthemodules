<?php /**
 * @file
 * Contains \Drupal\shorten\Controller\DefaultController.
 */

namespace Drupal\shorten\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the shorten module.
 */
class DefaultController extends ControllerBase {

  public function shorten_admin_form() {
    $form = \Drupal::formBuilder()->getForm('shorten_admin');
    return \Drupal::service("renderer")->render($form);
  }

  public function shorten_keys_form() {
    $form = \Drupal::formBuilder()->getForm('shorten_keys');
    return \Drupal::service("renderer")->render($form);
  }

  public function shorten_form_shorten_form() {
    $form = \Drupal::formBuilder()->getForm('shorten_form_shorten');
    return \Drupal::service("renderer")->render($form);
  }

}
