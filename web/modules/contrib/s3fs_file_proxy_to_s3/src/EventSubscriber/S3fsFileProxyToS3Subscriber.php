<?php

namespace Drupal\s3fs_file_proxy_to_s3\EventSubscriber;

use Drupal\Component\Utility\Unicode;
use Drupal\stage_file_proxy\EventSubscriber\ProxySubscriber;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * S3fs Stage file proxy to S3 subscriber for controller requests.
 */
class S3fsFileProxyToS3Subscriber extends ProxySubscriber {

  /**
   * Fetch the file according the its origin.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function checkFileOrigin(GetResponseEvent $event) {
    $file_dir = $this->manager->filePublicPath();
    $uri = $event->getRequest()->getPathInfo();

    $uri = Unicode::substr($uri, 1);

    if (strpos($uri, '' . $file_dir) !== 0) {
      return;
    }

    $uri = rawurldecode($uri);
    $relative_path = Unicode::substr($uri, Unicode::strlen($file_dir) + 1);

    $uri = "public://{$relative_path}";

    if (file_exists($uri)) {
      $url = file_create_url($uri);
      header("Location: $url");
      exit;
    }
    else {
      parent::checkFileOrigin($event);
    }
  }

}
