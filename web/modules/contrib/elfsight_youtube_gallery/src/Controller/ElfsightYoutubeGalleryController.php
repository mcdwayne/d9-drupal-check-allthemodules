<?php

namespace Drupal\elfsight_youtube_gallery\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * {@inheritdoc}
 */
class ElfsightYoutubeGalleryController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $params = [
      'user' => [
        'configEmail' => \Drupal::config('system.site')->get('mail'),
      ],
    ];

    $url = 'https://apps.elfsight.com/embed/yottie/?utm_source=portals&utm_medium=drupal&utm_campaign=youtube-gallery&utm_content=sign-up&params=' . urlencode(json_encode($params));

    require_once __DIR__ . '/embed.php';

    return [
      'response' => 1,
    ];
  }

}
