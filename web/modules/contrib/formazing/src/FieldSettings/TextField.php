<?php

namespace Drupal\formazing\FieldSettings;

use Drupal\formazing\FieldHelper\Properties\TextfieldProperties;
use Drupal\formazing\FieldViewer\Parser\TextfieldParser;

class TextField extends TextfieldProperties {

  /**
   * @param $entity
   * @return array
   */
  public static function generateSettings($entity) {
    $form = [];
    $form['name'] = parent::settingName($entity);
    $form['machine_name'] = parent::settingMachineName($entity);
    $form['is_showing_label'] = parent::settingShowingLabel($entity);
    $form['type'] = parent::settingType($entity);
    $form['value'] = parent::settingValue($entity);
    $form['description'] = parent::settingDescription($entity);
    $form['placeholder'] = parent::settingPlaceholder($entity);
    $form['prefix'] = parent::settingPrefix($entity);
    $form['suffix'] = parent::settingSuffix($entity);
    $form['is_required'] = parent::settingRequired($entity);
    $form['field_id'] = parent::settingFieldId($entity);
    $form['formazing_id'] = parent::settingFormazingId($entity);
    $form['submit'] = parent::settingSubmit();

    return $form;
  }

  /**
   * @return string
   */
  public static function getMachineTypeName() {
    return 'textfield';
  }

  /**
   * @return string
   */
  public static function getParser() {
    return TextfieldParser::class;
  }
}
