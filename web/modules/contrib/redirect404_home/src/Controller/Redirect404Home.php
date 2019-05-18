<?php

namespace Drupal\redirect404_home\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for default HTTP 404 responses.
 */
class Redirect404Home extends ControllerBase {

  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404() {
    $config = $this->config('redirect404_home.settings');
    $redirection = $config->get('redirection');
    $url = Url::fromRoute('system.404');
    $response = new RedirectResponse($url->toString(), $redirection);
    $status_message = $config->get('status_message');
    if (isset($status_message) && !empty($status_message)) {
      $status_message_color = $config->get('status_message_color');
      drupal_set_message($status_message, $status_message_color);
    }
    return $response;
  }

}
