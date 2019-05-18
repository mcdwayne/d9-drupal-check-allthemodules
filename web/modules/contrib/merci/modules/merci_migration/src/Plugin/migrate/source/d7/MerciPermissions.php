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
 *   id = "merci_permissions",
 * )
 */
class MerciPermissions extends NodeType {

  
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
    $query->condition('mt.merci_type_setting', ['bucket', 'resource'], 'IN');
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

    $type = $row->getSourceProperty('type');

    $permissions = $this->select('role_permission', 'rp')
      ->fields('rp', ['rid', 'permission'])
      ->condition('permission', 'edit own ' . $type . ' content')
      ->execute()
      ->fetchCol();
    if (!empty($permissions)) {
      $permissions = $this->select('role_permission', 'rp')
        ->fields('rp', ['rid', 'permission'])
        ->condition('permission', 'delete own ' . $type . ' content')
        ->condition('rid', $permissions, 'IN')
        ->execute()
        ->fetchAll();
    }
    $row->setSourceProperty('permissions', $permissions);

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
