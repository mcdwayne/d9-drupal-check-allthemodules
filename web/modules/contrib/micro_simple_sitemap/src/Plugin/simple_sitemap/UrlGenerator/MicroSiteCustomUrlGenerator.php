<?php

namespace Drupal\micro_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\CustomUrlGenerator;
use Drupal\Core\Path\PathValidator;

/**
 * Class CustomUrlGenerator.
 *
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "micro_site_custom",
 *   label = @Translation("Custom URL generator for micro site"),
 *   description = @Translation("Generates URLs set in XML sitemap settings page."),
 * )
 */
class MicroSiteCustomUrlGenerator extends CustomUrlGenerator {

  use MicroSiteUrlGeneratorTrait;

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $custom_links = [];
    $site_id = $this->getSiteId($this->sitemapVariant);
    $site = $this->entityTypeManager->getStorage('site')->load($site_id);
    if (!$site instanceof SiteInterface) {
      return $custom_links;
    }
    $custom_links = $site->getData('micro_simple_sitemap_custom_links') ?: [];
    foreach ($custom_links as $key => $link_config) {
      if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($link_config['path'])) {
        unset($custom_links[$key]);
        continue;
      }
      $url_object = Url::fromUserInput($link_config['path'], ['absolute' => TRUE]);
      $entity = $this->entityHelper->getEntityFromUrlObject($url_object);
      if ($entity instanceof ContentEntityBase && !$this->entityIsAffectedToSite($entity, $site_id)) {
        unset($custom_links[$key]);
      }

    }
    $custom_links = array_values($custom_links);
    return $custom_links;
  }

}
