<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\source\d5\Field.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\source\d5;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 5 field source from database.
 *
 * @MigrateSource(
 *   id = "d5_field",
 *   source_provider = "content"
 * )
 */
class Field extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node_field', 'cnf')
      ->fields('cnf', array(
        'field_name',
        'type',
        'global_settings',
        'required',
        'multiple',
        'db_storage',
        'module',
        'db_columns',
        'active',
        'locked',
      ))
      ->fields('cnfi', array(
        'widget_type',
        'widget_settings',
      ));
    $query->join('node_field_instance', 'cnfi', 'cnfi.field_name = cnf.field_name');
    $query->orderBy('field_name');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'field_name' => $this->t('Field name'),
      'type' => $this->t('Type (text, integer, ....)'),
      'global_settings' => $this->t('Global settings. Shared with every field instance.'),
      'required' => $this->t('Required'),
      'multiple' => $this->t('Multiple'),
      'db_storage' => $this->t('DB storage'),
      'module' => $this->t('Module'),
      'db_columns' => $this->t('DB Columns'),
      'active' => $this->t('Active'),
      'locked' => $this->t('Locked'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    // Unserialize data.
    $global_settings = unserialize($row->getSourceProperty('global_settings'));
    $widget_settings = unserialize($row->getSourceProperty('widget_settings'));
    $db_columns = unserialize($row->getSourceProperty('db_columns'));
    $row->setSourceProperty('global_settings', $global_settings);
    $row->setSourceProperty('widget_settings', $widget_settings);
    $row->setSourceProperty('db_columns', $db_columns);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['field_name'] = array(
      'type' => 'string',
      'alias' => 'cnf',
    );
    return $ids;
  }

}
