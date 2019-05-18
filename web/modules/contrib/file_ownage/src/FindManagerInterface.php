<?php

namespace Drupal\file_ownage;

use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Interface for FindManager.
 *
 * Declares what a FindManger service should do.
 */
interface FindManagerInterface {

  /**
   * Constructs an FetchManager instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $client, FileSystemInterface $file_system);

  /**
   * Tries to locate a named file, given some search rules and locations.
   *
   * @param string &$relative_path
   *   The expected path to the requested resource,
   *   relative to the files directory.
   *   This may get updated by reference if it is found elsewhere.
   * @param array $options
   *   Options for the request.
   *
   * @return int
   *   Returns the success of the lookup, an enum such as [IS_LOCAL, IS_REMOTE]
   *   valid location if the file was found, otherwise false.
   */
  public function find(&$relative_path, array $options);

  /**
   *
   */
  public function repair(&$uri);

  /**
   * @param $source
   *   A local or remote URL, or a filepath.
   *
   * @param $destination
   *   Where to save to, should include a filewrapper scheme such as public://
   *
   * @param int $status
   *   The file_ownage status flag, indicating if the source is local or whatever.
   *   Optional, will be checked if not given.
   *
   * @return bool
   */
  public function fetch($source, $destination, $status = NULL);

  /**
   *
   */
  public function fetchRemote($url);

  /**
   *
   */
  public function fetchLocal($url);

}
