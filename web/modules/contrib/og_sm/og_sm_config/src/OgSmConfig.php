<?php

namespace Drupal\og_sm_config;

use Drupal\node\NodeInterface;

/**
 * Site manager configuration helper methods.
 */
class OgSmConfig {

  /**
   * Gets the site config override object for the passed configuration name.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param string $name
   *   Configuration name.
   *
   * @return \Drupal\og_sm_config\Config\SiteConfigOverride
   *   Configuration override object.
   */
  public static function getOverride(NodeInterface $site, $name) {
    return static::siteConfigOverride()->getOverride($site, $name);
  }

  /**
   * Returns the site config override instance.
   *
   * @return \Drupal\og_sm_config\Config\SiteConfigFactoryOverrideInterface
   *   The site configuration override service.
   */
  public static function siteConfigOverride() {
    return \Drupal::service('og_sm.config_factory_override');
  }

}
