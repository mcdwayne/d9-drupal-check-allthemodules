<?php

namespace Drupal\merci_migration\Plugin\migrate\source\d7;
use Drupal\node\Plugin\migrate\source\d7\NodeType;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Drupal 7 node source from database.
 *
 * @MigrateSource(
 *   id = "merci_bucket",
 *   source_module = "node"
 * )
 */
class MerciBucket extends NodeType {

  
  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';
  
  /**
   * {@inheritdoc}
   */
  public function query() {
    

    $query = $this->select('node_type', 't')->fields('t');
    $query->join('merci_node_type', 'mt', 'mt.type = t.type');
    $query->condition('mt.merci_type_setting', 'bucket', '=');
    $query->fields('mt');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    $type = $row->getSourceProperty('type');

    $query = $this->select('variable', 'v')->fields('v')->condition('name', 'merci_grouping_' . $type);

    $result = $query
      ->execute()
      ->fetchAll();

    if (!empty($result)) {
      $tid = unserialize($result[0]['value']);

      if ($tid) {
        $row->setSourceProperty('resource_tree', $tid);
      }
    }

    // Select node in its last revision.
    $query = $this->select('node_revision', 'nr')->fields('n', [
      'nid',
      'type',
      'language',
      'status',
      'created',
      'changed',
      'comment',
      'promote',
      'sticky',
      'tnid',
      'translate',
    ])->fields('nr', [
      'vid',
      'title',
      'log',
      'timestamp',
    ]);
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);
    $query->leftJoin('merci_bucket_node', 'mbn', 'n.nid = mbn.nid');
    $query->condition('mbn.merci_sub_type', '1');
    $query->condition('type', $type);

    $result = $query
      ->execute()
      ->fetchAll();

    $row->setSourceProperty('bucket_items', $result);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    $ids['type']['alias'] = 't';
    return $ids;
  }
  


}
