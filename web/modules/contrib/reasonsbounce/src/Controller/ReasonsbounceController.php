<?php

/**
 * @file
 * Contains \Drupal\reasonsbounce\Controller\ReasonsbounceController.
 */

namespace Drupal\reasonsbounce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

class ReasonsbounceController extends ControllerBase {
  
  public function form($form_id) {
    $message = \Drupal::entityManager()
      ->getStorage('contact_message')
      ->create(array(
        'contact_form' => $form_id,
      ));
    $form = \Drupal::service('entity.form_builder')->getForm($message);

    $options = ['width' => '80%'];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t('Modal'), $form, $options));
    return $response;
  }
  
}
