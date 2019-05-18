<?php

namespace Drupal\formazing\FieldHelper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\FieldHelper\Properties\Property;

class FieldAction {

  /**
   * @param $entity
   * @param FormStateInterface $form_state
   */
  public static function saveField($entity, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      if ($entity->hasField($key)) {
        /** @var Property $type */
        $type = $entity->getFieldType();
        $type::setEntityValue($entity, $key, $value);
      }
    }

    $entity->save();
  }

  /**
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $a
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $b
   * @return int
   */
  public static function orderWeight($a, $b) {
    return $a->getWeight() < $b->getWeight() ? -1 : 1;
  }

  /**
   * @param $options
   * @return array
   */
  public static function filterEmptyOption($options) {
    if (!$options) {
      return [];
    }

    $options = array_filter($options, [
      FieldAction::class,
      'removeEmptyValue'
    ]);

    return $options;
  }

  /**
   * Remove empty options
   * @param $value
   * @return string
   */
  public static function removeEmptyValue($value) {
    return str_replace(' ', '', $value);
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $form
   * @return array
   */
  public static function cleanFormValues($form_state, $form) {
    $wrong_values = ['form_build_id', 'form_token', 'form_id', 'op'];
    $values = $form_state->getValues();

    $results = array_filter($values, function ($key) use ($wrong_values) {
      return !in_array($key, $wrong_values);
    }, ARRAY_FILTER_USE_KEY);

    /** @TODO refactor this shit */
    return array_map(function ($result, $key) use ($form) {
      $type = $form[$key]['#type'];
      $value = $result;

      if ('radios' === $type || 'select' === $type) {
        $value = $form[$key]['#options'][$result];
        $type = 'textfield';
      }
      else if ('checkboxes' === $type) {
        $value = '';
        foreach ($result as $k => $res) {
          if (0 === $res) {
            continue;
          }
          $value .= $form[$key]['#options'][$k] . ' || ';
        }
        $type = 'textfield';
      }
      return [
        'value' => $value,
        'label' => $key,
        'type' => $type,
      ];
    }, $results, array_keys($results));
  }

  /**
   * @param $machine_name
   *
   * @return bool
   */
  public static function validateMachineName($machine_name) {
    if (!$machine_name) {
      return FALSE;
    }

    $ids = \Drupal::entityQuery('field_formazing_entity')->execute();

    $results = FieldFormazingEntity::loadMultiple($ids);

    return array_filter($results, function (FieldFormazingEntity $field) use ($machine_name) {
      return !empty($machine_name === $field->getMachineName());
    });
  }
}
