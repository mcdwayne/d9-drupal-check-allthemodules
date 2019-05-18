<?php

namespace Drupal\experience\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity experience field.
 */
class ExperienceFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      $default_value = $this->getFieldDefinition()->getDefaultValueLiteral();
      $year_options = [];
      if ($this->getSetting('include_fresher')) {
        $year_options['fresher'] = t('Fresher');
      }
      $year_start = $this->getSetting('year_start');
      $year_end = $this->getSetting('year_end');

      $year_options += range($year_start, $year_end);

      $element['default'] = [
        '#parents' => ['default_value_input'],
        '#type' => 'fieldset',
        '#title' => $this->getFieldDefinition()->getLabel(),
        '#attributes' => [
          'class' => [
            'container-inline',
            'fieldgroup',
            'form-composite',
            'form-type-experience-select',
          ],
        ],
      ];

      $element['default']['default_year'] = [
        '#type' => 'select',
        '#title' => t('Years'),
        '#options' => $year_options,
        '#empty_option' => '',
        '#default_value' => isset($default_value[0]['default_year']) ? $default_value[0]['default_year'] : '',
        '#attributes' => ['class' => ['year-entry']],
      ];
      $element['default']['default_month'] = [
        '#type' => 'select',
        '#title' => t('Months'),
        '#options' => range(0, 11),
        '#empty_option' => '',
        '#default_value' => isset($default_value[0]['default_month']) ? $default_value[0]['default_month'] : '',
        '#attributes' => ['class' => ['month-entry']],
      ];

      if ($this->getSetting('label_position') == 'within') {
        $element['default']['default_year']['#empty_option'] = t('-Year');
        $element['default']['default_month']['#empty_option'] = t('-Month');
        $element['default']['default_year']['#title_display'] = 'invisible';
        $element['default']['default_month']['#title_display'] = 'invisible';
      }
      $element['#attached']['library'][] = 'experience/drupal.experience';
      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    return [$form_state->getValue('default_value_input')];
  }

}
