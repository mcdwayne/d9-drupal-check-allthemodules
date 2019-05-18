<?php

namespace Drupal\formazing\FieldHelper\Properties;

use Drupal\formazing\Entity\FieldFormazingEntity;

/**
 * Class Field
 * @package Drupal\formazing\FieldSettings
 */
abstract class CheckboxProperties extends Property implements PropertiesInterface {

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingValue($entity) {
    return $elements['value'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity->getFieldValue(),
      '#title' => t('Default value', [], ['context' => 'formazing']),
      '#required' => FALSE,
    ];
  }

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
      '#default_value' => $entity->getSuffix($entity),
      '#title' => t('Suffix', [], ['context' => 'formazing']),
      '#required' => FALSE,
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingRequired($entity) {
    return $elements['is_required'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity->isRequired(),
      '#title' => t('Required field', [], ['context' => 'formazing']),
    ];
  }

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingShowingLabel($entity) {
    return $elements['is_showing_label'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity->isShowingLabel(),
      '#title' => t('Show label', [], ['context' => 'formazing']),
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