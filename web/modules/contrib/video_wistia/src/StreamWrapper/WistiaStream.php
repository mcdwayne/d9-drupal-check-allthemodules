<?php

/**
 * @file
 * Contains \Drupal\video_wistia\StreamWrapper\WistiaStream.
 */

namespace Drupal\video_wistia\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\video\StreamWrapper\VideoRemoteStreamWrapper;

/**
 * Defines a WistiaStream (wistia://) stream wrapper class.
 */
class WistiaStream extends VideoRemoteStreamWrapper {
  
  protected static $base_url = 'http://home.wistia.com/medias';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Wistia');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Video served by the Wistia service.');
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseUrl() {
    return self::$base_url;
  }
}
