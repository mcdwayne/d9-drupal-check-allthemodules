<?php

namespace Drupal\file_encrypt;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\file_encrypt\StreamFilter\DecryptStreamFilter;
use Drupal\file_encrypt\StreamFilter\EncryptStreamFilter;

/**
 * Provides a scheme wrapper which encrypts / decrypts automatically.
 *
 * Therefore it has the encryption profile as part of the URL:
 *
 * @code
 * encrypt://example_profile/foo.txt
 * @endcode
 */
class EncryptStreamWrapper extends LocalStream {

  /**
   * Defines the schema used by the encrypt stream wrapper.
   */
  const SCHEME = 'encrypt';

  /**
   * An array of file info, each being an array.
   *
   * @var array[]
   */
  protected $fileInfo;

  /**
   * @var
   */
  protected $mode;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Encrypted files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Encrypted local files served by Drupal.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $profile = $this->extractEncryptionProfile($this->uri);
    $path = str_replace('\\', '/', $this->getTarget());

    // The image style already has a file path. included
    if (strpos($path, 'styles/') === 0) {
      $file = $path;
    }
    else {
      $file = $profile->id() . '/' . $path;
    }

    return Url::fromRoute('system.encrypt_file_download', ['filepath' => $file], ['absolute' => TRUE])
      ->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * Returns the base path for encrypted://.
   *
   * Note that this static method is used by \Drupal\system\Form\FileSystemForm
   * so you should alter that form or substitute a different form if you change
   * the class providing the stream_wrapper.encrypt service.
   *
   * @return string
   *   The base path for encrypt://.
   */
  public static function basePath() {
    return Settings::get('encrypted_file_path', '');
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    // This method adds the encryption profile to the URI.

    if (!$uri) {
      $uri = $this->uri;
    }
    $encryption_profile = parse_url($uri, PHP_URL_HOST);

    list($scheme) = explode('://', $uri, 2);
    $target = $this->getTarget($uri);
    $dirname = dirname($target);

    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $encryption_profile . '/' . $dirname;
  }

  /**
   * Decrypts a given file.
   *
   * @param string $raw_file
   *   The encrypted content of the raw file.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The used encryption profile.
   *
   * @return string
   *   The decrypted string.
   */
  protected function decrypt($raw_file, EncryptionProfileInterface $encryption_profile) {
    /** @var \Drupal\encrypt\EncryptService $encryption */
    $encryption = \Drupal::service('encryption');
    return $encryption->decrypt($raw_file, $encryption_profile);
  }

  /**
   * Encrypts a given file.
   *
   * @param string $raw_file
   *   The descrypted content of the raw file.
   * @param \Drupal\encrypt\EncryptionProfileInterface $encryption_profile
   *   The used encryption profile.
   *
   * @return string
   *   The encrypted string.
   */
  protected function encrypt($raw_file, EncryptionProfileInterface $encryption_profile) {
    /** @var \Drupal\encrypt\EncryptService $encryption */
    $encryption = \Drupal::service('encryption');
    return $encryption->encrypt($raw_file, $encryption_profile);
  }

  /**
   * Extracta the encryption profile from an URI.
   *
   * @param string $uri
   *   The URI of the encrypt URI.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\encrypt\EncryptionProfileInterface|null The encryption profile
   * The encryption profile
   *
   * @throws \Exception
   *   Thrown when the profile doesn't exist.
   */
  protected function extractEncryptionProfile($uri) {
    /** @var \Drupal\encrypt\EncryptionProfileManager $profile_manager */
    $profile_manager = \Drupal::service('encrypt.encryption_profile.manager');

    // Add support for image styles.
    if (preg_match('/^encrypt:\/\/styles\/\w+\/encrypt\/(\w+)/i', $uri, $match)) {
      $profile = $match[1];
    }
    else {
      $profile = parse_url($uri, PHP_URL_HOST);
    }

    $result = $profile_manager->getEncryptionProfile($profile);
    if (!$result) {
      throw new \Exception('Missing profile: ' . parse_url($uri, PHP_URL_HOST));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->ensureEncryptedFilesDirectory();
    parent::stream_open($uri, $mode, $options, $opened_path);
    $this->appendAllStreamFilters($uri);
    return (bool) $this->handle;
  }

  /**
   * Creates the encrypted files directory if it doesn't exist.
   */
  protected function ensureEncryptedFilesDirectory() {
    $directory = $this->getDirectoryPath();
    if ($directory && !file_exists($directory)) {
      mkdir($directory, 0755);
    }
  }

  /**
   * Appends all the stream filters.
   *
   * @param string $uri
   *   A string containing the URI to the file to open.
   */
  protected function appendAllStreamFilters($uri) {
    /** @var \Drupal\encrypt\EncryptService $encryption */
    $encryption = \Drupal::service('encryption');
    $params = [
      'encryption_service' => $encryption,
      'encryption_profile' => $this->extractEncryptionProfile($uri),
    ];
    self::appendStreamFilter($this->handle, EncryptStreamFilter::NAME, EncryptStreamFilter::class, STREAM_FILTER_WRITE, $params);
    self::appendStreamFilter($this->handle, DecryptStreamFilter::NAME, DecryptStreamFilter::class, STREAM_FILTER_READ, $params);
  }

  /**
   * Appends a single stream filter.
   *
   * @param resource $stream
   *   The stream to append the filter to.
   * @param string $filter_name
   *   The filter name.
   * @param string $class_name
   *   The filter class name.
   * @param int $read_write
   *   The filter chain to attach to: STREAM_FILTER_READ, STREAM_FILTER_WRITE,
   *   or STREAM_FILTER_ALL.
   * @param array $params
   *   An arbitrary array of parameters to pass to the filter.
   *
   * @internal
   */
  public static function appendStreamFilter($stream, $filter_name, $class_name, $read_write, array $params) {
    stream_filter_register($filter_name, $class_name);
    stream_filter_append($stream, $filter_name, $read_write, $params);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    // Add support for image styles.
    if (preg_match('/^encrypt:\/\/styles\/\w+\/encrypt\/(\w+)/i', $uri, $match)) {
      $target = str_replace('encrypt://', '', $uri);
    }
    else {
      $target = parse_url($uri, PHP_URL_PATH);
    }

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    return trim($target, '\/');
  }

  /**
   * Stores important info about the file we're operating on.
   *
   * @param string $content
   *   The content of the file
   * @param string $name
   *   The filename
   */
  protected function setFileInfo($content, $name) {
    $this->fileInfo[$name] = [
      'size' => strlen($content),
    ];
  }


}
