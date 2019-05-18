<?php

namespace Drupal\search_api_elasticsearch_attachments;

/**
 * {@inheritdoc}
 */
class Helpers {

  /**
   * Provides the Search API Index name.
   */
  public static function getIndexName($indexName) {
    // See IndexFactory:getIndexName()
    $options = \Drupal::database()->getConnectionOptions();
    $site_database = $options['database'];
    $site_database = strtolower(preg_replace('/[^A-Za-z0-9_]+/', '', $site_database));
    $sapiIndexName = str_replace('elasticsearch_index_' . $site_database . '_', '', $indexName);

    return $sapiIndexName;
  }

}
