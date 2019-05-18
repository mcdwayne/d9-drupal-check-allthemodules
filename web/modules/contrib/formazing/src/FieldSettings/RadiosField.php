<?php

namespace Drupal\formazing\FieldSettings;

use Drupal\formazing\FieldHelper\Properties\RadiosProperties;
use Drupal\formazing\FieldViewer\Parser\RadiosParser;

class RadiosField extends RadiosProperties {

  /**
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $entity
   *
   * @return array
   */
  public static function generateSettings($entity) {
    $options = $entity->get('field_options')->getValue();

    $form = [];

    $form['name'] = parent::settingName($entity);
    $form['machine_name'] = parent::settingMachineName($entity);
    $form['is_showing_label'] = parent::settingShowingLabel($entity);
    $form['type'] = parent::settingType($entity);
    $form['description'] = parent::settingDescription($entity);
    $form['#tree'] = TRUE;

    $form['options'] = [
      '#type' => 'container',
    ];

    if (count($options)) {
      foreach ($options as $key => $option) {
        $form['field_options'][$key] = [
          '#type' => 'textfield',
          '#title' => t('Option ' . ($key + 1)),
          '#default_value' => $option['value'],
          '#description' => t('You can remove this option by removing its value. On save, they will be reordered.'),
        ];
      }
    }
    else {
      $form['field_options'][0] = [
        '#type' => 'textfield',
        '#title' => t('Option ' . 1),
        '#description' => t('You can remove options by removing values. On save, they will be reordered.'),
      ];
    }

    // Button to add more names.
    $form['add_option'] = [
      '#type' => 'submit',
      '#value' => t('Add option'),
    ];

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
    return 'radios';
  }

  /**
   * @return string
   */
  public static function getParser() {
    return RadiosParser::class;
  }
}
