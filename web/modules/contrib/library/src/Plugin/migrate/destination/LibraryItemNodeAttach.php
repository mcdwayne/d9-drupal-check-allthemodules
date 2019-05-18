<?php

namespace Drupal\library\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Form to attach to node.
 *
 * @MigrateDestination(
 *   id = "libray_item_node_attach",
 *   provider = "library"
 * )
 */
class LibraryItemNodeAttach extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {

    $node = Node::load($row->getRawDestination()['nid']);
    if ($node) {
      $values = $node->get('library_item')->getValue();
      $values[] = ['target_id' => $row->getSourceProperty('id')];
      $node->set('library_item', $values);
      $node->save();
      return [$node->id()];
    }
    else {
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // This is intentionally left empty.
  }

  /**
   * Gets the entity.
   *
   * @param string $entity_type
   *   The entity type to retrieve.
   * @param string $bundle
   *   The entity bundle.
   * @param string $mode
   *   The display mode.
   * @param string $type
   *   The destination type.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   The entity display object.
   */
  protected function getEntity($entity_type, $bundle, $mode, $type) {
    $function = $type == 'entity_form_display' ? 'entity_get_form_display' : 'entity_get_display';
    return $function($entity_type, $bundle, $mode);
  }

}
