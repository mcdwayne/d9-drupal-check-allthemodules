<?php

namespace Drupal\sirv\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StreamWrapper\LocalStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Aws\S3\StreamWrapper;

/**
 * Defines a Sirv (sirv://) stream wrapper class.
 *
 * Provides support for storing and serving files with Sirv's image
 * processing and hosting platform.
 */
class SirvStream extends StreamWrapper implements StreamWrapperInterface {

  use StringTranslationTrait;

  const ID = 'sirv';

  /**
   * Instance URI URI (stream)
   *
   * A stream is referenced as "<scheme>://key".
   *
   * @var string
   */
  protected $uri;

  /**
   * The Sirv service.
   *
   * @var Drupal\sirv\SirvService
   */
  protected $sirvService;

  /**
   * The S3 client.
   *
   * @var Aws\S3\S3ClientInterface
   */
  protected $s3Client;

  /**
   * Sirv configuration.
   *
   * @var array
   */
  protected $config = [];

  /**
   * SirvStream constructor.
   */
  public function __construct() {
    $this->sirvService = \Drupal::service('sirv');

    $config = \Drupal::config('sirv.settings');
    foreach ($config->get() as $key => $value) {
      $this->config[$key] = $value;
    }

    $this->s3Client = $this->getS3Client();
  }

  /**
   * Get the S3 client.
   */
  private function getS3Client() {
    return $this->sirvService->getS3Client($this->config);
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Sirv');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Sirv image processing and hosting platform.');
  }

  /**
   * Gets the path for which this wrapper is responsible.
   *
   * This function isn't defined in DrupalStreamWrapperInterface, but Drupal
   * core calls it as if it were, so it needs to be defined.
   *
   * @return string
   *   An empty string, since this is a remote stream wrapper.
   *
   * @see https://www.drupal.org/project/drupal/issues/833734
   */
  public function getDirectoryPath() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Add and call static basePath and baseUrl methods, like in
   * PublicStream class.
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', file_uri_target($this->uri));

    // Add the root directory.
    if (!empty($this->config['root_dir'])) {
      $path = rtrim($this->config['root_dir'], '/') . '/' . $path;
    }

    // Add the domain.
    $external_url = rtrim($this->config['domain'], '/') . '/' . UrlHelper::encodePath($path);

    return $external_url;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support realpath().
   *
   * @return bool
   *   Always returns FALSE.
   */
  public function realpath() {
    return false;
  }

  /**
   * {@inheritdoc}
   *
   * Gets the name of the directory from a given path.
   *
   * This method is usually accessed through
   * \Drupal::service('file_system')->dirname(),
   * which wraps around the normal PHP dirname() function because it
   * does not support stream wrappers.
   *
   * @param string $uri
   *   An optional URI.
   *
   * @return string
   *   The directory name.
   *
   * @see \Drupal::service('file_system')->dirname()
   */
  public function dirname($uri = null) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $scheme = \Drupal::service('file_system')->uriScheme($uri);
    $dirname = dirname(file_uri_target($uri));

    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $dirname;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support flock().
   *
   * @return bool
   *   Always Returns FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
    return false;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support touch(), chmod(), chown(), or chgrp().
   *
   * Manual recommends return FALSE for not implemented options, but Drupal
   * require TRUE in some cases like chmod for avoid watchdog erros.
   *
   * Returns FALSE if the option is not included in bypassed_options array
   * otherwise, TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-metadata.php
   * @see \Drupal\Core\File\FileSystem::chmod()
   */
  public function stream_metadata($uri, $option, $value) {
    $bypassed_options = [STREAM_META_ACCESS];
    return in_array($option, $bypassed_options);
  }

  /**
   * {@inheritdoc}
   *
   * This method is not supported.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return false;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support stream_truncate.
   *
   * Always returns FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-truncate.php
   */
  public function stream_truncate($new_size) {
    return false;
  }

}
