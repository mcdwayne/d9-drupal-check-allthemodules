<?php

namespace Drupal\multiple_sitemap\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\multiple_sitemap\MultipleSitemapXml;

/**
 * Updates a multisitemap xml.
 *
 * @QueueWorker(
 *   id = "ms_update_xml",
 *   title = @Translation("Updated xml links"),
 *   cron = {"time" = 300}
 * )
 */
class MultipleSitemapUpdateXml extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data == 'sitemap_xml') {
      $multiplesitemapxml = new MultipleSitemapXml();
      $multiplesitemapxml->multiple_sitemap_create_xml_sitemap();
    }
  }
}
