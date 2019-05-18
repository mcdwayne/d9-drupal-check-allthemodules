<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\source\d5\NodeRevision.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\source\d5;

/**
 * Drupal 5 node revision source from database.
 *
 * @MigrateSource(
 *   id = "d5_node_revision"
 * )
 */
class NodeRevision extends Node {

  /**
   * The join options between the node and the node_revisions_table.
   */
  const JOIN = 'n.nid = nr.nid AND n.vid <> nr.vid';

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Use all the node fields plus the vid that identifies the version.
    return parent::fields() + array(
      'vid' => t('The primary identifier for this version.'),
      'log' => $this->t('Revision Log message'),
      'timestamp' => $this->t('Revision timestamp'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'nr';
    return $ids;
  }

}
