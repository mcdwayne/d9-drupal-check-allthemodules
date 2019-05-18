<?php

namespace Drupal\ofed_switcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OfedSwitcherSwitchController
 *
 * @package Drupal\ofed_switcher\Controller
 */
class OfedSwitcherSwitchController extends ControllerBase {

  public function go_to_frontend() {
    $url = $this->config('ofed_switcher.configuration')->get('frontend');

    return new RedirectResponse(Url::fromUserInput($url)->setAbsolute()->toString());
  }

  public function go_to_backend() {
    $url = $this->config('ofed_switcher.configuration')->get('backend');

    return new RedirectResponse(Url::fromUserInput($url)->setAbsolute()->toString());
  }

  public function switcher() {
    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute();

    if ($is_admin) {
      return $this->go_to_frontend();
    }

    return $this->go_to_backend();
  }

}
