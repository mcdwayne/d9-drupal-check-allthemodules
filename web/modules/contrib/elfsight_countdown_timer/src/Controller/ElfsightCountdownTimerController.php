<?php

namespace Drupal\elfsight_countdown_timer\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * {@inheritdoc}
 */
class ElfsightCountdownTimerController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $params = [
      'user' => [
        'configEmail' => \Drupal::config('system.site')->get('mail'),
      ],
    ];

    $url = 'https://apps.elfsight.com/embed/countdown-timer/?utm_source=portals&utm_medium=drupal&utm_campaign=countdown-timer&utm_content=sign-up&params=' . urlencode(json_encode($params));

    require_once __DIR__ . '/embed.php';

    return [
      'response' => 1,
    ];
  }

}
