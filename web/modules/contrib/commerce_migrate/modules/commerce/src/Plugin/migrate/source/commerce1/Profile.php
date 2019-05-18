<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 commerce_customer_profile source from database.
 *
 * @MigrateSource(
 *   id = "commerce1_profile",
 *   source_module = "commerce_customer"
 * )
 */
class Profile extends FieldableEntity {

  /**
   * The join options between commerce_customer_profile and its revision table.
   */
  const JOIN = 'cp.revision_id = cpr.revision_id';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('commerce_customer_profile_revision', 'cpr')
      ->fields('cpr');
    $query->innerJoin('commerce_customer_profile', 'cp', static::JOIN);
    $query->fields('cp');
    $query->addField('cpr', 'status', 'revision_status');
    $query->addField('cpr', 'data', 'revision_data');

    /** @var \Drupal\Core\Database\Schema $db */
    if ($this->getDatabase()->schema()->tableExists('commerce_addressbook_defaults')) {
      $query->leftJoin('commerce_addressbook_defaults', 'cad', 'cp.profile_id = cad.profile_id AND cp.uid = cad.uid AND cp.type = cad.type');
      $query->addField('cad', 'type', 'cad_type');
    }
    else {
      // If the currency column does not exist, add it as an expression to
      // normalize the query results.
      $query->addExpression(':cad', 'cad_type', [':cad' => FALSE]);
    }

    if (isset($this->configuration['profile_type'])) {
      $types = is_array($this->configuration['profile_type']) ? $this->configuration['profile_type'] : [$this->configuration['profile_type']];
      $query->condition('cp.type', $types, 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'profile_id' => $this->t('Profile ID'),
      'type' => $this->t('Type'),
      'uid' => $this->t('Owner'),
      'status' => $this->t('Status'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'data' => $this->t('Data blob'),
      'cad_type' => $this->t('Type, if matching entry in defaults table'),
      'revision_id' => t('The primary identifier for this version.'),
      'revision_uid' => t('The primary identifier for this revision.'),
      'log' => $this->t('Revision Log message'),
      'revision_timestamp' => $this->t('Revision timestamp'),
      'revision_data' => $this->t('The revision data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));
    $row->setSourceProperty('revision_data', unserialize($row->getSourceProperty('revision_data')));

    $profile_id = $row->getSourceProperty('profile_id');
    $revision_id = $row->getSourceProperty('revision_id');
    // Get Field API field values.
    foreach (array_keys($this->getFields('commerce_customer_profile', $row->getSourceProperty('type'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('commerce_customer_profile', $field, $profile_id, $revision_id));
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['profile_id']['type'] = 'integer';
    $ids['profile_id']['alias'] = 'cp';
    return $ids;
  }

}
