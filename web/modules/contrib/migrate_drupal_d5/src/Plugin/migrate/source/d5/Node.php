<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\source\d5\Node.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\source\d5;

use Drupal\node\Plugin\migrate\source\d6\Node as NodeBase;

/**
 * Drupal 5 node source from database. All that differs from Drupal 6 is that
 * we don't have the language, tnid, and translate columns.
 *
 * @MigrateSource(
 *   id = "d5_node"
 * )
 */
class Node extends NodeBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select node in its last revision.
    $query = $this->select('node_revisions', 'nr')
      ->fields('n', array(
        'nid',
        'type',
        'status',
        'created',
        'changed',
        'comment',
        'promote',
        'moderate',
        'sticky',
      ))
      ->fields('nr', array(
        'vid',
        'title',
        'body',
        'teaser',
        'log',
        'timestamp',
        'format',
      ));
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);

    if (isset($this->configuration['node_type'])) {
      $query->condition('type', $this->configuration['node_type']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    unset($fields['language']);
    unset($fields['tnid']);
    unset($fields['translate']);
    return $fields;
  }

}
