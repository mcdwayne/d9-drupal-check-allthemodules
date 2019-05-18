<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;

/**
 * Class MicroSiteSitemapType.
 *
 * @SitemapType(
 *   id = "micro_site",
 *   label = @Translation("Micro Site"),
 *   description = @Translation("The micro site sitemap type."),
 *   sitemapGenerator = "default",
 *   urlGenerators = {
 *     "micro_site_entity",
 *     "micro_site_entity_menu_link_content",
 *     "micro_site_custom"
 *   },
 * )
 */
class MicroSiteSitemapType extends SitemapTypeBase {
}
