<?php

namespace Drupal\set_front_page;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override.
 */
class ConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The fronpage manager.
   *
   * @var \Drupal\set_front_page\SetFrontPageManager
   */
  protected $setFrontPageManager;

  /**
   * Constructs a new ConfigOverride.
   *
   * @param \Drupal\set_front_page\SetFrontPageManager $setFrontPageManager
   *   The set_front_page manager.
   */
  public function __construct(SetFrontPageManager $setFrontPageManager) {
    $this->setFrontPageManager = $setFrontPageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('system.site', $names)) {
      $conf = $this->setFrontPageManager->getConfig();
      if ($conf['frontpage']) {
        $overrides['system.site'] = ['page' => ['front' => $conf['frontpage']]];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SetFrontPageConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
