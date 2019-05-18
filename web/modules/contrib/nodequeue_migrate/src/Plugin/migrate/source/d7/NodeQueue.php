<?php

namespace Drupal\nodequeue_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 nodequeue source from database.
 *
 * @MigrateSource(
 *   id = "d7_nodequeue",
 *   source_module = "nodequeue"
 * )
 */
class NodeQueue extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('nodequeue_queue', 'q')->fields('q', [
      'qid',
      'title',
      'size',
      'name',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'qid' => $this->t('Queue ID'),
      'title' => $this->t('Title'),
      'size' => $this->t('Maximum queue size'),
      'name' => $this->t('Machine name'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $target_bundles = $this->select('nodequeue_types', 'nt')
      ->fields('nt', ['type'])
      ->condition('nt.qid', $row->getSourceProperty('qid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('target_bundles', $target_bundles);

    $queues = $this->select('nodequeue_subqueue', 'ns')
      ->fields('ns')
      ->condition('ns.qid', $row->getSourceProperty('qid'))
      ->execute()
      ->fetchAll();
    $handler = count($queues) == 1 ? 'simple' : 'multiple';
    $row->setSourceProperty('handler', $handler);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['qid']['type'] = 'integer';
    return $ids;
  }

}
