<?php

namespace Drupal\vendor_stream_wrapper\StreamWrapper;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\vendor_stream_wrapper\Exception\VendorDirectoryNotFoundException;

/**
 * Creates a new vendor:// stream wrapper, for files in the vendor folder.
 */
class VendorStreamWrapper extends LocalReadOnlyStream implements StreamWrapperInterface {

  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Vendor Files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Vendor local files served by Drupal.');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $this->url('vendor_stream_wrapper.vendor_file_download', ['filepath' => $path], ['absolute' => TRUE, 'path_processing' => FALSE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * Returns the base path for vendor://.
   *
   * @return string
   *   The base path for vendor://.
   */
  public static function basePath() {
    if ($custom_path = Settings::get('vendor_file_path')) {
      return $custom_path;
    }
    elseif (is_dir('../vendor')) {
      return '../vendor';
    }
    elseif (is_dir('vendor')) {
      return 'vendor';
    }

    throw new VendorDirectoryNotFoundException();
  }

}
