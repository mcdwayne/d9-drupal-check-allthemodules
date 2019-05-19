<?php

namespace Drupal\third_party_wrappers;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Defines a service to handle common functionality for Third Party Wrappers.
 */
class ThirdPartyWrappers implements ThirdPartyWrappersInterface {

  /**
   * The file system wrapper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The stream wrapper.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapper;

  /**
   * The configuration object for Third Party Wrappers.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new ThirdPartyWrappers instance.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system wrapper.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper
   *   The stream wrapper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper, ConfigFactoryInterface $config_factory) {
    $this->fileSystem = $file_system;
    $this->streamWrapper = $stream_wrapper;
    $this->config = $config_factory->get('third_party_wrappers.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function cleanDirectory($path, $age) {
    foreach (file_scan_directory($path, '/.*/') as $file => $fileobj) {
      $atime = fileatime($file);
      if ($atime !== FALSE && $atime + $age < time()) {
        $this->fileSystem->unlink($file);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function copyFiles($template, $type) {
    $public_paths = $this->getFilePaths();
    $files_path_esc = $public_paths['files_path_esc'];

    // Automatically handle AdvAgg.
    foreach (['', 'advagg_'] as $prefix) {
      $matches = [];
      $search = '/' . $files_path_esc . '\/' . $prefix . $type . '\/' . $type . '_[^?"]*/is';
      preg_match_all($search, $template, $matches);
      if (!empty($matches[0])) {
        $third_party_wrappers_uri = file_default_scheme() . '://' . $this->config->get('css_js_dir');
        $copy_path = $third_party_wrappers_uri . '/' . $prefix . $type;
        file_prepare_directory($copy_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
        foreach ($matches[0] as $file) {
          file_unmanaged_copy(($file), $copy_path . '/' . basename($file), FILE_EXISTS_REPLACE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFilePaths() {
    $paths = [];
    // The system path for public files.
    $scheme = file_default_scheme();
    /** @var \Drupal\Core\StreamWrapper\LocalStream $wrapper */
    $wrapper = $this->streamWrapper->getViaScheme($scheme);
    $files_path = $wrapper->getDirectoryPath();
    // The files path with the slashes and periods escaped.
    $files_path_esc = addcslashes($files_path, '/.');

    $paths['files_path'] = $files_path;
    $paths['files_path_esc'] = $files_path_esc;

    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxAge() {
    $expire_age = $this->config->get('expire_age');
    if (empty($expire_age)) {
      return 0;
    }

    return $expire_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getSplitOn() {
    $split_on = $this->config->get('split_on');
    if (empty($split_on)) {
      return '';
    }

    return $split_on;
  }

  /**
   * {@inheritdoc}
   */
  public function getDir() {
    $dir = $this->config->get('css_js_dir');
    if (empty($dir)) {
      return '';
    }

    return $dir;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    $dir = $this->getDir();
    $uri = file_default_scheme() . '://' . $this->getDir();
    if (empty($dir) || empty($uri)) {
      return '';
    }

    return $uri;
  }

}
