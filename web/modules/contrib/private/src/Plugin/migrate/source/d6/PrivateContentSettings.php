<?php

namespace Drupal\private_content\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Migrates private content type settings.
 *
 * @MigrateSource(
 *   id = "d6_private_content_settings"
 * )
 */
class PrivateContentSettings extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node_type', 't')
      ->fields('t', array('type'));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $node_type = $row->getSourceProperty('type');
    $setting = $this->variableGet('private_' . $node_type, 1);
    $row->setSourceProperty('private', $setting);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'id' => $this->t('Private content type.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
