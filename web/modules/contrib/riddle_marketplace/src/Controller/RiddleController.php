<?php

namespace Drupal\riddle_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class RiddleController.
 *
 * @package Drupal\riddle_marketplace\Controller
 */
class RiddleController extends ControllerBase {

  /**
   * Create Render array for riddle iframe.
   *
   * @return array
   *   Render array for Riddle Iframe.
   */
  public function riddleIframe() {

    $config = $this->config(
      'riddle_marketplace.settings'
    );
    $token = $config->get('riddle_marketplace.token');

    if ($token) {
      return [
        '#theme' => 'riddle_backend',
        '#token' => $token,
      ];
    }
    else {
      drupal_set_message($this->t('Please provide an access token in the <a href="/@link">configuration form</a>.', [
        '@link' => Url::fromRoute('riddle_marketplace.admin_settings')->getInternalPath(),
      ]), 'warning');
      return [];
    }
  }

}
