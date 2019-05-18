<?php

namespace Drupal\bibcite_migrate\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Source plugin for the contributors.
 *
 * @MigrateSource(
 *   id = "bibcite_keyword",
 *   source_provider = "biblio",
 *   source_module = "biblio"
 * )
 */
class Keyword extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('biblio_keyword_data', 'bkd')
      ->fields('bkd');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'kid' => $this->t('Keyword ID'),
      'word' => $this->t('Word'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'kid' => [
        'type' => 'integer',
        'alias' => 'bkd',
      ],
    ];
  }

}
