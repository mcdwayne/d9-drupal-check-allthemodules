<?php

namespace Drupal\private_content\Plugin\migrate\destination\d6;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Imports per node private settings.
 *
 * @MigrateDestination(
 *   id = "d6_private_content"
 * )
 */
class PrivateContent extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {

    $nid = $row->getSourceProperty('nid');
    $private = $row->getSourceProperty('private');
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $node->set('private', $private);
    $node->save();

    return [0 => $nid];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'nid' => 'The nid',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

}
