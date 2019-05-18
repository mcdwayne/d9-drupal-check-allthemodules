<?php

namespace Drupal\bibcite_migrate\Plugin\migrate\source;


use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Source plugin for the contributors.
 *
 * @MigrateSource(
 *   id = "bibcite_contributor",
 *   source_provider = "biblio",
 *   source_module = "biblio"
 * )
 */
class Contributor extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('biblio_contributor_data', 'bcd')
      ->fields('bcd');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Name'),
      'lastname' => $this->t('Last name'),
      'firstname' => $this->t('First name'),
      'prefix' => $this->t('Prefix'),
      'suffix' => $this->t('Suffix'),
      'initials' => $this->t('Initials'),
      'affiliation' => $this->t('Affiliation'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'cid' => [
        'type' => 'integer',
        'alias' => 'bcd',
      ],
    ];
  }

}
