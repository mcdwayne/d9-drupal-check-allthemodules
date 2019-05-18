<?php

namespace Drupal\date_recur_status\Plugin\DateRecurOccurrenceHandler;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\date_recur\DateRecurRRule;
use Drupal\date_recur\Plugin\DateRecurOccurrenceHandler\DefaultDateRecurOccurrenceHandler;
use Drupal\date_recur\Plugin\DateRecurOccurrenceHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * @DateRecurOccurrenceHandler(
 *  id = "date_recur_status_occurrence_handler",
 *  label = @Translation("Status enabled occurrence handler"),
 * )
 */
class DateRecurStatusOccurrenceHandler extends DefaultDateRecurOccurrenceHandler implements DateRecurOccurrenceHandlerInterface, ContainerFactoryPluginInterface {

  public function getDefaultStatus() {
    $list = array_keys($this->getStatusList());
    return $list[0];
  }

  public function getStatusList() {
    $config = \Drupal::config('date_recur_status.settings');
    $statuses = $config->get('statuses');
    return $statuses;
  }

  public function getOccurrencesForDisplay($start = NULL, $end = NULL, $num = NULL) {
    if (!$this->isRecurring) {
      return [];
    }
    $entity_id = $this->item->getEntity()->id();
    $revision_id = $this->item->getEntity()->getRevisionId();
    $field_name = $this->item->getFieldDefinition()->getName();
    $storage_format = $this->item->getDateStorageFormat();
    $field_delta = $this->item->getDelta();

    $q = $this->database->select($this->tableName)
      ->fields($this->tableName);
    $q->condition('entity_id', $entity_id);
    $q->condition('revision_id', $revision_id);
    $q->condition('field_delta', $field_delta);
    $q->orderBy('delta');

    if (!empty($start)) {
      $start = DateRecurRRule::massageDateValueForStorage($start, $storage_format);
      $q->condition($field_name . '_value', $start, '>=');
    }
    if (!empty($end)) {
      $end = DateRecurRRule::massageDateValueForStorage($start, $storage_format);
      $q->condition($field_name . '_end_value', $end, '<=');
    }
    if (!empty($num)) {
      $q->range(0, $num);
    }

    $res = $q->execute()->fetchAll();
    $occurrences = [];
    foreach ($res as $row) {
      $row = (array) $row;
      $occurrences[] = [
        'value' => $this->convertDateForDisplay($row[$field_name . '_value']),
        'end_value' => $this->convertDateForDisplay($row[$field_name . '_end_value']),
        'status' => !empty($row['status']) ? $row['status'] : $this->getDefaultStatus(),
        'delta' => $row['delta'],
        'field_delta' => $row['field_delta'],
      ];
    }
    return $occurrences;
  }

  protected function convertDateForDisplay($date) {
    $dateObj = DrupalDateTime::createFromFormat($this->item->getDateStorageFormat(), $date, new \DateTimeZone($this->item->timezone));
    // Adjust timezone manually to prevent daylight saving time changes.
    if ($offset = $this->rruleObject->getTimezoneOffset()) {
      $dateObj->add(new \DateInterval('PT' . $offset . 'S'));
    }
    return $dateObj;
  }

  public function updateStatusField($field_delta, $delta, $value) {
    $entity_id = $this->item->getEntity()->id();
    $revision_id = $this->item->getEntity()->getRevisionId();
    $q = $this->database->update($this->tableName);
    $q->condition('field_delta', $field_delta);
    $q->condition('delta', $delta);
    $q->condition('entity_id', $entity_id);
    $q->condition('revision_id', $revision_id);
    $q->fields(['status' => $value]);
    $q->execute();
  }

  public function onSave($update, $field_delta) {
    $entity = $this->item->getEntity();
    $entity_id = $entity->id();
    $field_name = $this->item->getFieldDefinition()->getName();
    $revision_id = $old_revision_id = $entity->getRevisionId();
    if ($entity->isNewRevision() && isset($entity->original)) {
      $old_revision_id = $entity->original->getRevisionId();
    }
    $dates = $this->getOccurrencesForCacheStorage();
    $defaultStatus = $this->getDefaultStatus();

    $existing = [];
    if ($update) {
      $q = $this->database->select($this->tableName)
        ->fields($this->tableName);
      $q->condition('entity_id', $entity_id);
      $q->condition('field_delta', $field_delta);
      $q->condition('revision_id', $old_revision_id);
      foreach ($q->execute() as $row) {
        $row = (array) $row;
        $existing[$row[$field_name . '_value']] = $row;
      }
    }

    $fields = ['entity_id', 'revision_id', 'field_delta', $field_name . '_value', $field_name . '_end_value', 'delta', 'status'];
    $delta = 0;
    $rows = [];
    foreach ($dates as $date) {
      $status = !empty($existing[$date['value']]) ? $existing[$date['value']]['status'] : $defaultStatus;
      $rows[] = [
        'entity_id' => $entity_id,
        'revision_id' => $revision_id,
        'field_delta' => $field_delta,
        $field_name . '_value' => $date['value'],
        $field_name . '_end_value' => $date['end_value'],
        'delta' => $delta,
        'status' => $status,
      ];
      $delta++;
    }

    if ($update) {
      // Delete all existing rows.
      // @todo: Revision support.
      $this->database->delete($this->tableName)
        ->condition('entity_id', $entity_id)
        ->condition('field_delta', $field_delta)
        ->execute();
    }
    // Insert rows.
    $q = $this->database->insert($this->tableName)->fields($fields);
    foreach ($rows as $row) {
      $q->values($row);
    }
    $q->execute();
  }


  public function getOccurrenceTableSchema(FieldStorageDefinitionInterface $field) {
    $schema = parent::getOccurrenceTableSchema($field);
    $schema['fields']['status'] = [
      'type' => 'varchar',
      'length' => 255,
      'description' => 'Status',
    ];
    return $schema;
  }

  public function onFieldUpdate(FieldStorageConfigInterface $field) {
    $table_name = $this->getOccurrenceTableName($field);
    if (!$this->database->schema()->fieldExists($table_name, 'status')) {
      $schema = $this->getOccurrenceTableSchema($field);
      $this->database->schema()->addField($table_name, 'status', $schema['fields']['status']);
    }
  }

  public function viewsData(FieldStorageConfigInterface $field_storage, $data) {
    $data = parent::viewsData($field_storage, $data);
    $label = $field_storage->label();
    $recur_table_name = $this->getOccurrenceTableName($field_storage);
    $data[$recur_table_name]['status'] = [
      'title' => t('@field: status', ['@field' => $label]),
      'title short' => t('@field: status', ['@field' => $label]),
      'group' => $this->t('Content'),
      'field' => [
        'table' => $recur_table_name,
        'field' => 'status',
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'field' => 'status',
        'table' => $recur_table_name,
        'id' => 'string',
        'allow empty' => TRUE,
      ],
      'sort' => [
        'field' => 'status',
        'table' => $recur_table_name,
        'id' => 'standard',
        'allow empty' => TRUE,
      ],
      'argument' => [
        'field' => 'status',
        'table' => $recur_table_name,
        'id' => 'string',
      ],
    ];

    return $data;
  }

  public function occurrencePropertyDefinition(FieldStorageDefinitionInterface $field_definition) {
    /** @var ListDataDefinition $occurrences */
    $occurrences = parent::occurrencePropertyDefinition($field_definition);
    /** @var MapDataDefinition $itemDefinition */
    $itemDefinition = $occurrences->getItemDefinition();
    $itemDefinition->setPropertyDefinition('status', DataDefinition::create('string')
      ->setLabel('Status'));
    $occurrences->setItemDefinition($itemDefinition);
    return $occurrences;
  }

  /**
   * @inheritdoc
   */
  public function getOccurrencesForComputedProperty() {
    $occurrences = $this->getOccurrencesForDisplay();
    $values = [];
    foreach ($occurrences as $delta => $occurrence) {
      $values[] = [
        'value' => $occurrence['value'],
        'end_value' => $occurrence['end_value'],
        'status' => $occurrence['status']
      ];
    }
    return $values;
  }
}
