<?php

namespace Drupal\elfsight_facebook_like_button\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * {@inheritdoc}
 */
class ElfsightFacebookLikeButtonController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $params = [
      'user' => [
        'configEmail' => \Drupal::config('system.site')->get('mail'),
      ],
    ];

    $url = 'https://apps.elfsight.com/embed/facebook-like-button/?utm_source=portals&utm_medium=drupal&utm_campaign=facebook-like-button&utm_content=sign-up&params=' . urlencode(json_encode($params));

    require_once __DIR__ . '/embed.php';

    return [
      'response' => 1,
    ];
  }

}
