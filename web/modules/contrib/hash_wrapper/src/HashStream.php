<?php

/**
 * @file
 * Definition of Drupal\hash_wrapper\HashStream.
 */

namespace Drupal\hash_wrapper;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a Drupal hash (hash://) stream wrapper class.
 *
 * Provides support for storing publicly accessible files with the Drupal file
 * interface.
 */
class HashStream extends PublicStream {

  /**
   * {@inheritdoc}
   */
  protected function getLocalPath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $basedir = $this->getDirectoryPath();
    $path = $basedir . '/' . $this->uriTarget($uri, $basedir);
    $realpath = realpath($path);
    if (!$realpath) {
      // This file does not yet exist.
      $realpath = realpath(dirname($path)) . '/' . basename($path);
    }
    $directory = realpath($basedir);
    if (!$realpath || !$directory || strpos($realpath, $directory) !== 0) {
      return FALSE;
    }
    return $realpath;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $basedir = $this->getDirectoryPath();
    $path = str_replace('\\', '/', $this->uriTarget($this->uri, $basedir));
    return $GLOBALS['base_url'] . "/$basedir/$path";
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode, $options) {
    $this->uri = $uri;
    $recursive = (bool)($options & STREAM_MKDIR_RECURSIVE);
    if ($recursive) {
      // $this->getLocalPath() fails if $uri has multiple levels of directories
      // that do not yet exist.
      $basedir = $this->getDirectoryPath();
      $localpath = $basedir . '/' . $this->uriTarget($uri, $basedir);
    }
    else {
      $localpath = $this->getLocalPath($uri);
    }
    if ($options & STREAM_REPORT_ERRORS) {
      return mkdir($localpath, $mode, $recursive);
    }
    else {
      return @mkdir($localpath, $mode, $recursive);
    }
  }

  /**
   * {@inheritdoc}
   */
  function uriTarget($uri, $basedir) {
    $filepath = file_uri_target($uri);
    // If all we got is hash://directory or even just hash:// then we can't
    // really continue.
    if (!$filepath || strpos($filepath, '.') === FALSE) {
      return $filepath;
    }
    $directory = dirname($filepath);
    $dir_parts = explode('/', $directory);
    if (count($dir_parts) >= 2 && Unicode::strlen(array_pop($dir_parts)) == 2 && Unicode::strlen(array_pop($dir_parts)) == 2) {
      return $filepath;
    }
    $file_parts = explode('.', $filepath);
    $count = count($file_parts);
    $extension = ($count > 1) ? '.' . array_pop($file_parts) : '';
    $basedir .= "/$directory";

    // Remove styles/$style/hash path before generating the hash.
    $filepath = preg_replace('/styles\/.+\/hash\//', '', $filepath);

    $target = md5($filepath) . $extension;
    $level1 = "$target[0]$target[1]";
    $level2 = "$target[2]$target[3]";
    if (!is_dir("$basedir/$level1/$level2")) {
      drupal_mkdir("$basedir/$level1/$level2", NULL, TRUE);
    }

    return "$directory/$level1/$level2/$target";
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Public local files in hash dir served by the webserver.');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Hashed public files');
  }

}
