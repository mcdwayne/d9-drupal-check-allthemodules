<?php

namespace Drupal\elfsight_facebook_reviews\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * {@inheritdoc}
 */
class ElfsightFacebookReviewsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $params = [
      'user' => [
        'configEmail' => \Drupal::config('system.site')->get('mail'),
      ],
    ];

    $url = 'https://apps.elfsight.com/embed/facebook-reviews/?utm_source=portals&utm_medium=drupal&utm_campaign=facebook-reviews&utm_content=sign-up&params=' . urlencode(json_encode($params));

    require_once __DIR__ . '/embed.php';

    return [
      'response' => 1,
    ];
  }

}
