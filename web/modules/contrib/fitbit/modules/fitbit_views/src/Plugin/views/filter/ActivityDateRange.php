<?php

namespace Drupal\fitbit_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Fitbit activity date range.
 *
 * @ViewsFilter("fitbit_activity_date_range")
 */
class ActivityDateRange extends FilterPluginBase  {
  protected $alwaysMultiple = TRUE;

  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['value'] = [
      'default' => [
        'type' => 'period',
        'date' => 'today',
        'period' => '7d',
        'base_date' => '',
        'end_date' => '',
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->value['base_date']) && !empty($this->value['end_date'])) {
      $summary = $this->value['base_date'] . '/' . $this->value['end_date'];
    }
    else {
      $summary = (isset($this->value['date']) ? $this->value['date'] : 'today') . '/' . (isset($this->value['period']) ? $this->value['period'] : '7d');
    }
    return $this->operator . ' ' . $summary;
  }


  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value']['type'] = [
      '#type' => 'radios',
      '#options' => [
        'period' => $this->t('Period'),
        'range' => $this->t('Date range'),
      ],
      '#title' => $this->t('Date type'),
      '#default_value' => $this->value['type'],
    ];

    $form['value']['date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date'),
      '#description' => $this->t('The end date of the period specified in the format <strong>yyyy-MM-dd</strong> or <strong>today</strong>.'),
      '#default_value' => $this->value['date'],
      '#states' => [
        'visible' => [
          ':input[name="options[value][type]"]' => ['value' => 'period'],
        ],
      ],
    ];

    $form['value']['period'] = [
      '#type' => 'select',
      '#title' => $this->t('Period'),
      '#description' => $this->t('The range for which data will be returned.'),
      '#options' => [
        '1d' => $this->t('1 day'),
        '7d' => $this->t('7 days'),
        '30d' => $this->t('30 days'),
        '1w' => $this->t('1 week'),
        '1m' => $this->t('1 month'),
        '3m' => $this->t('3 months'),
        '6m' => $this->t('6 months'),
        '1y' => $this->t('1 year'),
      ],
      '#default_value' => $this->value['period'],
      '#states' => [
        'visible' => [
          ':input[name="options[value][type]"]' => ['value' => 'period'],
        ],
      ],
    ];

    $form['value']['base_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base date'),
      '#description' => $this->t('The range start date, in the format <strong>yyyy-MM-dd</strong> or <strong>today</strong>.'),
      '#default_value' => $this->value['base_date'],
      '#states' => [
        'visible' => [
          ':input[name="options[value][type]"]' => ['value' => 'range'],
        ],
      ],
    ];

    $form['value']['end_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End date'),
      '#description' => $this->t('The end date of the randge, in the format <strong>yyyy-MM-dd</strong> or <strong>today</strong>.'),
      '#default_value' => $this->value['end_date'],
      '#states' => [
        'visible' => [
          ':input[name="options[value][type]"]' => ['value' => 'range'],
        ],
      ],
    ];
  }
}
