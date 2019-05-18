<?php

namespace Drupal\formazing\FieldSettings;

use Drupal\formazing\FieldHelper\Properties\TextfieldProperties;
use Drupal\formazing\FieldViewer\Parser\ButtonParser;

class SubmitField extends TextfieldProperties {

  /**
   * @param $entity
   * @return array
   */
  public static function generateSettings($entity) {
    $form = [];
    $form['name'] = parent::settingName($entity);
    $form['machine_name'] = parent::settingMachineName($entity);
    $form['type'] = parent::settingType($entity);
    $form['prefix'] = parent::settingPrefix($entity);
    $form['suffix'] = parent::settingSuffix($entity);
    $form['field_id'] = parent::settingFieldId($entity);
    $form['formazing_id'] = parent::settingFormazingId($entity);
    $form['submit'] = parent::settingSubmit();

    return $form;
  }

  /**
   * @return string
   */
  public static function getMachineTypeName() {
    return 'submit';
  }

  /**
   * @return string
   */
  public static function getParser() {
    return ButtonParser::class;
  }
}
