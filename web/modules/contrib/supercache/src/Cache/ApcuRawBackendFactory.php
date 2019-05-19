<?php

/**
 * @file
 * Contains \Drupal\supercache\Cache\ApcuRawBackendFactory.
 */

namespace Drupal\supercache\Cache;

use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheFactoryInterface;

class ApcuRawBackendFactory implements CacheRawFactoryInterface {

  /**
   * The cache tags checksum provider.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * Constructs the ApcuRawBackendFactory
   * 
   * @param string $root
   *   The site's root.
   * @param string $site_path 
   *   The site's path.
   */
  function __construct($root, $site_path) {
    $this->sitePrefix = Settings::getApcuPrefix('apcu_backend', $root, $site_path);
  }

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return ApcuRawBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {
    return new ApcuRawBackend($bin, $this->sitePrefix);
  }

}
