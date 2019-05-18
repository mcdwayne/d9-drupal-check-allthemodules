<?php

namespace Drupal\experience\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'experience_default' widget.
 *
 * @FieldWidget(
 *   id = "experience_default",
 *   label = @Translation("Experience"),
 *   field_types = {
 *     "experience"
 *   }
 * )
 */
class ExperienceDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = $item->value;
    if (isset($value) && $value == 0) {
      $match = [0 => 'fresher', 1 => 0];
    }
    elseif (!empty($value)) {
      if ($value > 11) {
        $year = floor($value / 12);
        $month = $value % 12;
      }
      else {
        $year = 0;
        $month = $value;
      }
      $match = [0 => $year, 1 => $month];
    }
    else {
      $match = [0 => '', 1 => ''];
    }

    $default_value = $item->getFieldDefinition()->getDefaultValueLiteral();
    $year_options = [];
    if ($this->getFieldSetting('include_fresher')) {
      $year_options['fresher'] = $this->t('Fresher');
    }
    $year_start = $this->getFieldSetting('year_start');
    $year_end = $this->getFieldSetting('year_end');

    $year_options += range($year_start, $year_end);

    $element += [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'container-inline',
          'fieldgroup',
          'form-composite',
          'form-type-experience-select',
        ],
      ],
    ];

    $element['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Years'),
      '#options' => $year_options,
      '#empty_option' => '',
      '#default_value' => isset($match[0]) ? $match[0] : $default_value[0]['default_year'],
      '#attributes' => ['class' => ['year-entry']],
    ];
    $element['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Months'),
      '#options' => range(0, 11),
      '#empty_option' => '',
      '#default_value' => isset($match[1]) ? $match[1] : $default_value[0]['default_month'],
      '#attributes' => ['class' => ['month-entry']],
    ];

    if ($this->getFieldSetting('label_position') == 'within') {
      $element['year']['#empty_option'] = $this->t('-Year');
      $element['month']['#empty_option'] = $this->t('-Month');
      $element['year']['#title_display'] = 'invisible';
      $element['month']['#title_display'] = 'invisible';
    }
    $element['#attached']['library'][] = 'experience/drupal.experience';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if ($item['year'] == '' && $item['month'] == '') {
        $value = '';
      }
      else {
        if ($item['year'] == 'fresher') {
          $value = 0;
        }
        else {
          $year = $item['year'] ? $item['year'] * 12 : 0;
          $month = $item['month'] ? $item['month'] : 0;
          $value = $year + $month;
        }
      }
    }
    $item['value'] = $value;
    return $values;
  }

}
