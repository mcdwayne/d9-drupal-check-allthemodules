<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\source\d5\TermNode.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\source\d5;

use Drupal\taxonomy\Plugin\migrate\source\d6\TermNode as TermNodeBase;

/**
 * Source returning tids from the term_node table for the current revision.
 *
 * @MigrateSource(
 *   id = "d5_term_node",
 *   source_provider = "taxonomy"
 * )
 */
class TermNode extends TermNodeBase {

    /**
   * The join options between the node and the term node table.
   */
  const JOIN = 'tn.nid = n.nid';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('term_node', 'tn')
      ->distinct()
      ->fields('n', array('nid', 'type'));
    $query->innerJoin('term_data', 'td', 'td.tid = tn.tid AND td.vid = :vid', array(':vid' => $this->configuration['vid']));
    $query->innerJoin('node', 'n', static::JOIN);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    // No term_node revisions in D5.
    unset($fields['vid']);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'tn';
    return $ids;
  }

}
