<?php

namespace Drupal\formazing\FieldHelper\Properties;

use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\FieldHelper\FieldAction;

abstract class Property {

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingMachineName($entity) {
    return $elements['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->getMachineName() ?: '',
      '#maxlength' => 64,
      '#description' => t('A unique name for this item. It must only contain lowercase letters, numbers, and underscores.', [],
        ['context' => 'formazing']),
      '#machine_name' => [
        'source' => ['name'],
        'exists' => [
          FieldAction::class,
          'validateMachineName'
        ]
      ],
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingName($entity) {
    return $elements['name'] = [
      '#type' => 'textfield',
      '#default_value' => $entity->getName(),
      '#title' => t('Label', [], ['context' => 'formazing']),
      '#required' => TRUE,
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingDescription($entity) {
    return $elements['description'] = [
      '#type' => 'textfield',
      '#default_value' => $entity->getDescription(),
      '#title' => t('Description', [], ['context' => 'formazing']),
      '#required' => FALSE,
      '#description' => t('If you want to add a help text for this field', [], ['context' => 'formazing']),
      '#maxlength' => 2058,
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingType($entity) {
    /** @var \Drupal\formazing\FieldSettings\TextField $type */
    $type = $entity->getFieldType();
    return $elements['type'] = [
      '#type' => 'textfield',
      '#default_value' => $type::getMachineTypeName(),
      '#title' => t('Type of field', [], ['context' => 'formazing']),
      '#required' => TRUE,
      '#attributes' => array(
        'readonly' => 'readonly',
        'disabled' => 'disabled'
      ),
    ];
  }

  /**
   * @return array
   */
  public static function settingSubmit() {
    return $elements['submit'] = [
      '#type' => 'submit',
      '#value' => t('Confirm this field', [], ['context' => 'formazing']),
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @param $key
   * @param $value
   */
  public static function setEntityValue($entity, $key, $value) {
    $entity->set($key, $value);
  }
}
