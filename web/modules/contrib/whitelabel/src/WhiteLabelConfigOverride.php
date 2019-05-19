<?php

namespace Drupal\whitelabel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override class for white label.
 *
 * Allows certain site wide variables (site name, slogan, logo) to be overridden
 * programmatically.
 *
 * This class cannot use dependency injection for the white label provider, as
 * that caused a circular dependency.
 */
class WhiteLabelConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Override the right logo. See https://www.drupal.org/node/2866194
   */
  public function loadOverrides($names) {
    $overrides = [];

    /* @var \Drupal\whitelabel\WhiteLabelInterface $whitelabel */
    if (
      (in_array('system.site', $names) || in_array('system.theme.global', $names)) &&
      ($whitelabel = \Drupal::service('whitelabel.whitelabel_provider')->getWhiteLabel())
    ) {

      /* @var \Drupal\Core\Field\FieldItemListInterface $fields */
      $fields = $whitelabel->getFields(FALSE);

      if (in_array('system.site', $names)) {
        if ($fields['name']->access()) {
          $site_name = !empty($whitelabel->getName()) ? $whitelabel->getName() : NULL;
          $overrides['system.site']['name'] = $site_name;
        }
        if ($fields['slogan']->access()) {
          $site_slogan = !empty($whitelabel->getSlogan()) ? $whitelabel->getSlogan() : NULL;
          $overrides['system.site']['slogan'] = $site_slogan;
        }
      }

      if (in_array('system.theme.global', $names)) {
        if ($fields['logo']->access()) {
          $site_logo = !empty($whitelabel->getLogo()) ? $whitelabel->getLogo()->getFileUri() : NULL;

          $overrides['system.theme.global']['logo']['path'] = $site_logo;
          $overrides['system.theme.global']['logo']['url'] = '';
          $overrides['system.theme.global']['logo']['use_default'] = FALSE;
        }
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'WhiteLabelConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();

    if ($name === 'system.site' || $name === 'system.theme.global') {
      // Include a no-white label page variant.
      $metadata->addCacheContexts(['whitelabel']);

      /* @var \Drupal\whitelabel\WhiteLabelInterface $whitelabel */
      if ($whitelabel = \Drupal::service('whitelabel.whitelabel_provider')->getWhiteLabel()) {
        // Here we add the cache tags, so we are aware when the entity updates.
        $metadata->addCacheableDependency($whitelabel);
      }
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
