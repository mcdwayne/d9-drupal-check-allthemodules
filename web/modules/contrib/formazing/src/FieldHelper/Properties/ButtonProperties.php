<?php

namespace Drupal\formazing\FieldHelper\Properties;

use Drupal\formazing\Entity\FieldFormazingEntity;

/**
 * Class Field
 * @package Drupal\formazing\FieldSettings
 */
abstract class ButtonProperties extends Property implements PropertiesInterface {

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingPrefix($entity) {
    return $elements['prefix'] = [
      '#type' => 'textfield',
      '#default_value' => $entity->getPrefix(),
      '#title' => t('Prefix', [], ['context' => 'formazing']),
      '#required' => FALSE,
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingSuffix($entity) {
    return $elements['suffix'] = [
      '#type' => 'textfield',
      '#default_value' => $entity->getSuffix(),
      '#title' => t('Suffix', [], ['context' => 'formazing']),
      '#required' => FALSE,
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingFieldId($entity) {
    return $elements['field_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->id(),
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingFormazingId($entity) {
    return $elements['formazing_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->getFormId(),
    ];
  }
}