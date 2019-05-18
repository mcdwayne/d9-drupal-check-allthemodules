<?php

namespace Drupal\private_content\Plugin\migrate\destination\d6;

use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\pager\SqlBase;

/**
 * Imports private content type settings.
 *
 * @MigrateDestination(
 *   id = "d6_private_content_settings"
 * )
 */
class PrivateContentSettings extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {

    $node_type = $row->getSourceProperty('type');
    $private = $row->getSourceProperty('private');
    /** @var \Drupal\Node\NodeTypeInterface $type */
    $type = NodeType::load($node_type);
    $type->setThirdPartySetting('private', 'private', $private);
    $type->save();

    return [0 => $node_type];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'type' => 'The node type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
