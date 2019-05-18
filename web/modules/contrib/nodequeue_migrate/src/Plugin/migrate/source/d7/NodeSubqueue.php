<?php

namespace Drupal\nodequeue_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 nodesubqueue source from database.
 *
 * @MigrateSource(
 *   id = "d7_nodesubqueue",
 *   source_module = "nodequeue"
 * )
 */
class NodeSubqueue extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('nodequeue_subqueue', 'sq')->fields('sq');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'sqid' => $this->t('Sub-queue ID'),
      'qid' => $this->t('Queue ID'),
      'reference' => $this->t('Reference'),
      'title' => $this->t('Title'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $names = $this->select('nodequeue_queue', 'nq')
      ->fields('nq', ['name'])
      ->condition('nq.qid', $row->getSourceProperty('qid'))
      ->execute()
      ->fetchCol();
    $name = reset($names);
    $row->setSourceProperty('name', $name);

    $items = $this->select('nodequeue_nodes', 'nn')
      ->fields('nn', ['position', 'nid'])
      ->condition('nn.sqid', $row->getSourceProperty('sqid'))
      ->execute()
      ->fetchAllKeyed();
    $row->setSourceProperty('items', $items);

    $queues = $this->select('nodequeue_subqueue', 'ns')
      ->fields('ns')
      ->condition('ns.qid', $row->getSourceProperty('qid'))
      ->execute()
      ->fetchAll();
    $sq_name = count($queues) == 1 ? $name : $row->getSourceProperty('sqid');
    $row->setSourceProperty('sq_name', $sq_name);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['sqid']['type'] = 'integer';
    return $ids;
  }

}
