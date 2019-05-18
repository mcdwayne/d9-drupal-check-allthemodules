<?php

namespace Drupal\cloudmersivensfw;

use Drupal\file\FileInterface;
use Drupal\cloudmersivensfw\Config;

/**
 * Provides an interface defining a menu entity.
 */
interface ScannerInterface {

  /**
   * Constructor.
   *
   * @param Drupal\cloudmersivensfw\Config $config
   *   Configuration to use.
   */
  public function __construct(Config $config);

  /**
   * Scan a file.
   *
   * @param Drupal\file\FileInterface $file
   *   The file to scan for viruses.
   *
   * @return int
   *   - Scanner::FILE_IS_CLEAN
   *   - Scanner::FILE_IS_INFECTED
   *   - Scanner::FILE_IS_UNCHECKED
   */
  public function scan(FileInterface $file);

  /**
   * The version of the CloudmersiveNsfw service.
   *
   * @return string
   *   The version number provided by CloudmersiveNsfw.
   */
  public function version();
}
