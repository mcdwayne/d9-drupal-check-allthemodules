<?php

namespace Drupal\domain_simple_sitemap;

use Drupal\simple_sitemap\Batch as SimpleSitemapBatch;

/**
 * Batch class.
 */
class Batch extends SimpleSitemapBatch {

  /**
   * {@inheritdoc}
   */
  public function addDomainOperation($plugin_id, $domain, $data_sets = NULL) {
    $this->batch['operations'][] = [
      __CLASS__ . '::domainGenerate', [
        $plugin_id,
        $domain, $data_sets,
        $this->batchSettings,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function domainGenerate($plugin_id, $domain, $data_sets, array $batch_settings, &$context) {
    $context['domain'] = $domain;
    \Drupal::service('plugin.manager.simple_sitemap.url_generator')
      ->createInstance($plugin_id)
      ->setContext($context)
      ->setBatchSettings($batch_settings)
      ->generate($data_sets);
  }

}
