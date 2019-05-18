<?php

namespace Drupal\dcat_import\Plugin\migrate\source;

/**
 * DCAT Dataset keyword feed source.
 *
 * @MigrateSource(
 *   id = "dcat.dataset_keyword"
 * )
 */
class DatasetKeywordDcatFeedSource extends TermDcatFeedSource {

  /**
   * {@inheritdoc}
   */
  public function getDcatType() {
    return 'dcat:Dataset';
  }

  /**
   * {@inheritdoc}
   */
  public function getTermField() {
    return 'dcat:keyword';
  }

}
