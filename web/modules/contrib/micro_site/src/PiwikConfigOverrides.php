<?php

namespace Drupal\micro_site;

use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Override Piwik configuration per micro site..
 *
 * @package Drupal\micro_site
 */
class PiwikConfigOverrides implements ConfigFactoryOverrideInterface {

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

    if (in_array('piwik.settings', $names)) {
      $site = $this->getActiveSite();
      if ($site instanceof SiteInterface) {
        $piwik_id = $site->getPiwikId();
        $piwik_url = $site->getPiwikUrl();
        if ($piwik_id && $piwik_url) {
          $overrides['piwik.settings'] = [
            'site_id' => $piwik_id,
            'url_http' => 'http://' . $piwik_url,
            'url_https' => 'https://' . $piwik_url,
          ];
        }
      }
    }
    if (in_array('matomo.settings', $names)) {
      $site = $this->getActiveSite();
      if ($site instanceof SiteInterface) {
        $piwik_id = $site->getPiwikId();
        $piwik_url = $site->getPiwikUrl();
        if ($piwik_id && $piwik_url) {
          $overrides['matomo.settings'] = [
            'site_id' => $piwik_id,
            'url_http' => 'http://' . $piwik_url,
            'url_https' => 'https://' . $piwik_url,
          ];
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
    return 'PiwikConfigurationOverrider';
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
