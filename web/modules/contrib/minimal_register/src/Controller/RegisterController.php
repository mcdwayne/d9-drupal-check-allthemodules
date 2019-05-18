<?php

namespace Drupal\minimal_register\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * Defines HelloController class.
 */
class RegisterController extends ControllerBase {
  public function content() {
    return;
  }
  public function verifyMail($timestamp) {
    $url = Url::fromRoute('<front>');
    $config = $this->config('minimal_register.settings');
    // Get Current User
    $current_user = User::load(\Drupal::currentUser()->id());
    if ($timestamp == $current_user->getCreatedTime()) {
      $current_user->addRole($config->get('role_selected'));
      $current_user->save();
    }
    return new RedirectResponse($url->toString());
  }
}

