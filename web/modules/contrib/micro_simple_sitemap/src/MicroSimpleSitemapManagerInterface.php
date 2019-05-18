<?php

namespace Drupal\micro_simple_sitemap;

use Drupal\micro_site\Entity\SiteInterface;

/**
 * Handles the negotiation of the active domain record.
 */
interface MicroSimpleSitemapManagerInterface {

  /**
   * Get the variant name for a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   *
   * @return string
   *   The variant name for a micro site.
   */
  public function getVariantName(SiteInterface $site);

  /**
   * Create a sitemap variant given a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createSitemapVariant(SiteInterface $site);

  /**
   * Remove a sitemap variant given a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function removeSitemapVariant(SiteInterface $site);

  /**
   * Set default bundle settings given a variant name.
   *
   * @param string $variant_name
   *   The variant name.
   * @param array $default_bundle_settings
   *   Optional. If empty, global default bundle settings are get.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setDefaultBundleSettingsVariant($variant_name, $default_bundle_settings = []);

  /**
   * Get all the variants given a sitemap type.
   *
   * @param string $sitemap_type
   *   The variant type.
   *
   * @return array
   *   An array of variant name.
   */
  public function getSitemapVariants($sitemap_type);

  /**
   * Publish a sitemap variant given a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function publishSitemapVariant(SiteInterface $site);

}
