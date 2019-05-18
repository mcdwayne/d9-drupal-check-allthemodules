<?php

namespace Drupal\library\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 library source.
 *
 * @MigrateSource(
 *   id = "d6_library_content_types"
 * )
 */
class LibraryContentTypes extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $inner_query = $this->select('node_type', 'nt');
    $inner_query->addExpression('CONCAT(\'library_\',type)', 'type');

    $query = $this->select('variable', 'v');
    $query->addExpression('SUBSTRING(name FROM 9)', 'name');
    $query->condition('value', serialize('1'), '=')
      ->condition('name', $inner_query, 'IN');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => 'The content types needing library fields',
      'value' => 'Always 1',
    ];
  }

  /**
   * Defines the source fields uniquely identifying a source row.
   *
   * None of these fields should contain a NULL value. If necessary, use
   * prepareRow() or hook_migrate_prepare_row() to rewrite NULL values to
   * appropriate empty values (such as '' or 0).
   *
   * @return array
   *   Array keyed by source field name, with values being a schema array
   *   describing the field (such as ['type' => 'string]).
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
