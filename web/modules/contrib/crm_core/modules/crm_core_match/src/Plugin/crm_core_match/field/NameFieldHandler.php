<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

use Drupal\crm_core_contact\ContactInterface;

/**
 * Class for evaluating name fields.
 *
 * @CrmCoreMatchFieldHandler (
 *   id = "name"
 * )
 */
class NameFieldHandler extends FieldHandlerBase {

  protected $configuration = [
    'title' => [
      'score' => 3,
    ],
    'given' => [
      'score' => 10,
    ],
    'middle' => [
      'score' => 1,
    ],
    'family' => [
      'score' => 10,
    ],
    'generational' => [
      'score' => 1,
    ],
    'credentials' => [
      'score' => 1,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function getPropertyNames() {
    return [
      'title',
      'given',
      'middle',
      'family',
      'generational',
      'credentials',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      'CONTAINS' => t('Contains'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function match(ContactInterface $contact, $property = 'family') {
    $field_name = $this->field->getName();
    $valid_parts = [];
    if ($contact->get($field_name)->{$property}) {
      $valid_parts[$property] = $contact->get($field_name)->{$property};
    }

    // Get the matches.
    $matches = [];
    foreach ($valid_parts as $property => $value) {
      $query = $this->entityTypeManager->getStorage('crm_core_individual')->getQuery();
      $query->condition('type', $contact->bundle());
      if ($contact->id()) {
        $query->condition('individual_id', $contact->id(), '<>');
      }
      $query->condition($field_name . '.' . $property, $value, 'CONTAINS');
      $ids = $query->execute();
      foreach ($ids as $id) {
        $matches[$id] = $this->getScore($property);
      }
    }

    arsort($matches);
    $result = [];
    foreach ($matches as $id => $score) {
      $result[$id] = [
        $this->field->getName() . '.' . $property => $score,
      ];
    }
    return $result;
  }

}
