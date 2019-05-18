<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

use Drupal\crm_core_contact\ContactInterface;

/**
 * Class for evaluating date fields.
 */
class DateFieldHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    $operators = [
      '=' => t('Equals'),
      '>=' => t('Greater than'),
      '<=' => t('Less than'),
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Update to new query API.
   */
  public function match(ContactInterface $contact, $property = 'value') {
    $results = [];
    $field_item = 'value';
    $field = field_get_items('crm_core_contact', $contact, $rule->field_name);
    $needle = isset($field[0]['value']) ? $field[0]['value'] : '';

    if (!empty($needle)) {
      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', 'crm_core_contact')->entityCondition('bundle', $contact->type)
        ->entityCondition('entity_id', $contact->contact_id, '<>')
        ->fieldCondition($rule->field_name, $field_item, $needle, $rule->operator);

      $results = $query->execute();
    }

    return isset($results['crm_core_contact']) ? array_keys($results['crm_core_contact']) : $results;
  }

}

/**
 * Just extender of DateMatchField to catch field type.
 */
class DateTimeFieldHandler extends DateFieldHandler {
}

/**
 * Just extender of DateMatchField to catch field type.
 */
class DateStampFieldHandler extends DateFieldHandler {
}
