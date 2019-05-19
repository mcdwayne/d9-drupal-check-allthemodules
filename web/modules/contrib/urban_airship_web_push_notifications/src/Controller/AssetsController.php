<?php

namespace Drupal\urban_airship_web_push_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Airship Web Notifications Assets.
 */
class AssetsController extends ControllerBase {

  /**
   * Generate /push-worker.js file
   */
  public function pushWorkerJs() {
    return $this->getFileContents('push-worker.js', 'application/javascript');
  }

  /**
   * Generate /web-push-secure-bridge.html file
   */
  public function secureBridgeHtml() {
    return $this->getFileContents('secure-bridge.html', 'text/html');
  }

  /**
   * Helper method to pull contents of the specified SDK Bundle file.
   */
  protected function getFileContents($filename, $content_type) {
    $config = \Drupal::config('urban_airship_web_push_notifications.configuration');
    $response = new CacheableResponse();
    $build = [
      '#markup' => $config->get($filename),
      '#cache' => [
        'tags' => ['urban_airship_web_push_notifications_assets'],
      ],
    ];
    $response->setCharset('UTF-8');
    $response->headers->set('Content-Type', $content_type);
    $response->setContent($build['#markup']);
    $cm = CacheableMetadata::createFromRenderArray($build);
    $response->addCacheableDependency($cm);
    return $response;
  }

}
