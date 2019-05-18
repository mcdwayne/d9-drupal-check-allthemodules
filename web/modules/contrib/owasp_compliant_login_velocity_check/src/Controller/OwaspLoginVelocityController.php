<?php
/**
 * @file
 * Contains \Drupal\hello_world\Controller\OwaspLoginVelocityController.
 */
namespace Drupal\owasp_login_velocity_check\Controller;
class OwaspLoginVelocityController {

  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello, World!'),
    );
  }

}