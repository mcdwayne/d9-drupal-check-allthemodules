<?php

namespace Drupal\micro_sitemap;

use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Override sitemap configuration per micro site.
 *
 * @package Drupal\micro_site
 */
class MicroSitemapConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * The active micro site or NULL.
   *
   * @var \Drupal\micro_site\Entity\SiteInterface|NULL
   */
  protected $activeSite = NULL;

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('sitemap.settings', $names)) {
      $site = $this->getActiveSite();
      if ($site instanceof SiteInterface) {
        $data = $site->getData('micro_sitemap');
        if ($data) {
          $overrides['sitemap.settings'] = $data;
        }
      }
    }

    return $overrides;
  }

  protected function getActiveSite() {
    // I don't use here a dependency injection because of a
    // CircularReferenceException thrown when injecting the negotiator.
    if (is_null($this->activeSite)) {
      /** @var \Drupal\micro_site\SiteNegotiatorInterface $negotiator */
      $negotiator = \Drupal::service('micro_site.negotiator');
      /** @var \Drupal\micro_site\Entity\SiteInterface $site */
      $this->activeSite = $negotiator->getActiveSite();
    }
    return $this->activeSite;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'MicroSitemapConfigurationOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $meta = new CacheableMetadata();
    return $meta;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
