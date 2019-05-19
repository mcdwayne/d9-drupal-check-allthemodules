<?php

namespace Drupal\wordpress_db_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * WordPress term migration source plugin.
 *
 * @MigrateSource(
 *   id = "wp_term"
 * )
 */
class WpTerm extends WpSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'term_id' => $this->t('Term ID.'),
      'name' => $this->t('Term name.'),
      'slug' => $this->t('Term slug.'),
      'term_taxonomy' => $this->t('Term taxonomy.'),
      'term_description' => $this->t('Term description.'),
      'parent' => $this->t('Term parent.'),
      'term_meta' => $this->t('Term meta information.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['term_id']['type'] = 'integer';
    $ids['term_id']['alias'] = 't';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('terms', 't');
    $query->fields('t', ['term_id', 'name', 'slug']);
    $query->orderBy('t.term_id');

    $query->innerJoin('term_taxonomy', 'tt', 'tt.term_id = t.term_id');
    $query->fields('tt', ['taxonomy', 'description', 'parent']);

    if (!empty($this->configuration['taxonomy'])) {
      $query->condition('tt.taxonomy', (array) $this->configuration['taxonomy'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('term_meta', $this->getMetaValues(
      'termmeta',
      'term_id',
      $row->getSourceProperty('term_id')));

    return parent::prepareRow($row);
  }

}
