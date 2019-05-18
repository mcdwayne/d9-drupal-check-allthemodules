<?php

namespace Drupal\formazing\FieldHelper\Properties;

use Drupal\formazing\Entity\FieldFormazingEntity;

interface PropertiesInterface {

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingName($entity);

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingType($entity);

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingPrefix($entity);

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingSuffix($entity);

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingRequired($entity);

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingFieldId($entity);

  /**
   * @param FieldFormazingEntity $entity
   * @return array
   */
  public static function settingFormazingId($entity);

  /**
   * @return array
   */
  public static function settingSubmit();

}
