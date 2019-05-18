<?php

namespace Drupal\high_contrast;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration override class for high contrast.
 *
 * Overrides the site logo if high contrast is enabled.
 */
class HighContrastConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Override the right logo. See https://www.drupal.org/node/2866194
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('system.theme.global', $names) && HighContrastTrait::high_contrast_enabled() && $logo = $this->getHighContrastLogo()) {
      $overrides['system.theme.global']['logo']['path'] = $logo;
      $overrides['system.theme.global']['logo']['url'] = '';
      $overrides['system.theme.global']['logo']['use_default'] = FALSE;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'HighContrastConfigOverride';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Check the right $name. See https://www.drupal.org/node/2866194
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();

    if ($name === 'system.theme.global') {
      $config = $this->configFactory->get('high_contrast.settings');

      // Cache depends on enabled state and configuration.
      $metadata->addCacheContexts(['high_contrast']);
      $metadata->addCacheableDependency($config);
  }

    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * Returns the configured logo, either from theme dir of configured path.
   */
  private function getHighContrastLogo() {
    $logo = NULL;

    $config = $this->configFactory->get('high_contrast.settings');

    if ($config->get('default_logo')) {
      // If the default logo is desired, scan the theme dir for a logo-hg file.
      $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
      $theme_path = drupal_get_path('theme', $theme);

      $candidates = file_scan_directory($theme_path, "/logo_hg\.(svg|png|jpg|gif)$/");
      if ($candidates) {
        $logo = reset($candidates)->uri;
      }
    }
    elseif($config->get('logo_path')) {
      // No default logo, return the custom logo instead.
      $logo = $config->get('logo_path');
    }

    return $logo;
  }

}
