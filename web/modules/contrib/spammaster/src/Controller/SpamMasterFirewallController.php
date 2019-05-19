<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class controller.
 */
class SpamMasterFirewallController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function spammasterfirewall() {

    return [
      '#theme' => 'firewall',
      '#type' => 'page',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
    ];
  }

}
