<?php

namespace Drupal\s3fs_file_proxy_to_s3;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Site\Settings;

/**
 * The S3fsFileProxyToS3ServiceProvider class.
 */
class S3fsFileProxyToS3ServiceProvider implements ServiceModifierInterface {

  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    if (Settings::get('s3fs.use_s3_for_public')) {
      // Replace the public stream wrapper with PublicS3fsFileProxyToS3Stream.
      $container->getDefinition('stream_wrapper.public')
        ->setClass('Drupal\s3fs_file_proxy_to_s3\StreamWrapper\PublicS3fsFileProxyToS3Stream');

      $container->getDefinition('stage_file_proxy.fetch_manager')
        ->setClass('Drupal\s3fs_file_proxy_to_s3\S3fsFileProxyToS3FetchManager');

      $container->getDefinition('stage_file_proxy.subscriber')
        ->setClass('Drupal\s3fs_file_proxy_to_s3\EventSubscriber\S3fsFileProxyToS3Subscriber');
    }
  }

}
