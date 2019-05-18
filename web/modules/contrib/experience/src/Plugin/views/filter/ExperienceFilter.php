<?php

namespace Drupal\experience\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides filtering by language.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("experience")
 */
class ExperienceFilter extends NumericFilter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label_position'] = ['default' => 'above'];
    $options['include_fresher'] = ['default' => 0];
    $options['year_start'] = ['default' => 0];
    $options['year_end'] = ['default' => 30];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();
    unset($operators['regular_expression']);
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $options = ['above' => $this->t('Above'), 'within' => $this->t('Within')];
    $description = $this->t("The location of experience part labels, like 'Year', 'Month'. 'Above' displays the label as titles above each experience part. 'Within' inserts the label as the first option in the select list.");
    $form['label_position'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $this->options['label_position'],
      '#title' => $this->t('Position of experience part labels'),
      '#description' => $description,
    ];

    $form['include_fresher'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->options['include_fresher'],
      '#title' => $this->t('Include fresher option'),
    ];

    $form['year_start'] = [
      '#type' => 'select',
      '#options' => range(0, 99),
      '#default_value' => $this->options['year_start'],
      '#title' => $this->t('Starting year'),
    ];

    $form['year_end'] = [
      '#type' => 'select',
      '#options' => range(0, 99),
      '#default_value' => $this->options['year_end'],
      '#title' => $this->t('Ending year'),
    ];
  }

  /**
   * Provide a simple textfield for equality.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value']['#tree'] = TRUE;

    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // Exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(2)) ? 'minmax' : 'value';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    $value = $this->value['value'];

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

    $year_options = [];
    if ($this->options['include_fresher']) {
      $year_options['fresher'] = $this->t('Fresher');
    }
    $year_start = $this->options['year_start'];
    $year_end = $this->options['year_end'];

    $year_options += range($year_start, $year_end);

    $user_input = $form_state->getUserInput();
    if ($which == 'all') {
      $form['value'] = [
        '#type' => 'fieldset',
        '#title' => !$exposed ? $this->t('Value') : '',
        '#attributes' => [
          'class' => [
            'container-inline',
            'fieldgroup',
            'form-composite',
            'form-type-experience-select',
          ],
        ],
      ];

      $form['value']['year'] = [
        '#type' => 'select',
        '#title' => $this->t('Years'),
        '#options' => $year_options,
        '#empty_option' => '',
        '#default_value' => isset($match[0]) ? $match[0] : '',
        '#attributes' => ['class' => ['year-entry']],
      ];
      $form['value']['month'] = [
        '#type' => 'select',
        '#title' => $this->t('Months'),
        '#options' => range(0, 11),
        '#empty_option' => '',
        '#default_value' => isset($match[1]) ? $match[1] : '',
        '#attributes' => ['class' => ['month-entry']],
      ];

      if ($this->options['label_position'] == 'within') {
        $form['value']['year']['#empty_option'] = $this->t('-Year');
        $form['value']['month']['#empty_option'] = $this->t('-Month');
        $form['value']['year']['#title_display'] = 'invisible';
        $form['value']['month']['#title_display'] = 'invisible';
      }
      // Setup #states for all operators with one value.
      foreach ($this->operatorValues(1) as $operator) {
        $form['value']['value']['#states']['visible'][] = [
          $source => ['value' => $operator],
        ];
      }
      if ($exposed && !isset($user_input[$identifier]['value'])) {
        $user_input[$identifier]['value'] = $this->value['value'];
        $form_state->setUserInput($user_input);
      }
    }
    elseif ($which == 'value') {
      // When exposed we drop the value-value and just do value if
      // the operator is locked.
      $form['value'] = [
        '#type' => 'fieldset',
        '#title' => !$exposed ? $this->t('Value') : '',
        '#attributes' => [
          'class' => [
            'container-inline',
            'fieldgroup',
            'form-composite',
            'form-type-experience-select',
          ],
        ],
      ];

      $form['value']['year'] = [
        '#type' => 'select',
        '#title' => $this->t('Years'),
        '#options' => $year_options,
        '#empty_option' => '',
        '#default_value' => isset($match[0]) ? $match[0] : '',
        '#attributes' => ['class' => ['year-entry']],
      ];
      $form['value']['month'] = [
        '#type' => 'select',
        '#title' => $this->t('Months'),
        '#options' => range(0, 11),
        '#empty_option' => '',
        '#default_value' => isset($match[1]) ? $match[1] : '',
        '#attributes' => ['class' => ['month-entry']],
      ];

      if ($this->options['label_position'] == 'within') {
        $form['value']['year']['#empty_option'] = $this->t('-Year');
        $form['value']['month']['#empty_option'] = $this->t('-Month');
        $form['value']['year']['#title_display'] = 'invisible';
        $form['value']['month']['#title_display'] = 'invisible';
      }
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value['value'];
        $form_state->setUserInput($user_input);
      }
    }

    if ($which == 'all' || $which == 'minmax') {
      $form['value']['min'] = [
        '#type' => 'textfield',
        '#title' => !$exposed ? $this->t('Min') : $this->exposedInfo()['label'],
        '#size' => 30,
        '#default_value' => $this->value['min'],
        '#description' => !$exposed ? '' : $this->exposedInfo()['description'],
      ];
      $form['value']['max'] = [
        '#type' => 'textfield',
        '#title' => !$exposed ? $this->t('And max') : $this->t('And'),
        '#size' => 30,
        '#default_value' => $this->value['max'],
      ];
      if ($which == 'all') {
        $states = [];
        // Setup #states for all operators with two values.
        foreach ($this->operatorValues(2) as $operator) {
          $states['#states']['visible'][] = [
            $source => ['value' => $operator],
          ];
        }
        $form['value']['min'] += $states;
        $form['value']['max'] += $states;
      }
      if ($exposed && !isset($user_input[$identifier]['min'])) {
        $user_input[$identifier]['min'] = $this->value['min'];
      }
      if ($exposed && !isset($user_input[$identifier]['max'])) {
        $user_input[$identifier]['max'] = $this->value['max'];
      }

      if (!isset($form['value'])) {
        // Ensure there is something in the 'value'.
        $form['value'] = [
          '#type' => 'value',
          '#value' => NULL,
        ];
      }
    }
    $form['#attached']['library'][] = 'experience/drupal.experience';
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    $year_val = $form_state->getValue(['options', 'value', 'year']);
    $month_val = $form_state->getValue(['options', 'value', 'month']);

    if ($year_val == '' && $month_val == '') {
      $value = NULL;
    }
    else {
      if ($year_val == 'fresher') {
        $value = 0;
      }
      else {
        $year = $year_val ? $year_val * 12 : 0;
        $month = $month_val ? $month_val : 0;
        $value = $year + $month;
      }
    }
    $form_state->unsetValue(['options', 'value', 'year']);
    $form_state->unsetValue(['options', 'value', 'month']);
    $form_state->setValue(['options', 'value', 'value'], $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    if ($this->value['value'] !== NULL) {
      $this->query->addWhere($this->options['group'], $field, $this->value['value'], $this->operator);
    }
  }

  /**
   * Helper function to see if we need to swap in the default value.
   *
   * Views exposed filters treat everything as submitted, so if it's an empty
   * value we have to see if anything actually was submitted. If nothing has
   * really been submitted, we need to swap in our default value logic.
   */
  protected function getFilterValue($input) {
    if (!empty($input)) {
      if ($input['year'] == '' && $input['month'] == '') {
        $value = NULL;
      }
      else {
        if ($input['year'] == 'fresher') {
          $value = 0;
        }
        else {
          $year = $input['year'] ? $input['year'] * 12 : 0;
          $month = $input['month'] ? $input['month'] : 0;
          $value = $year + $month;
        }
      }
    }
    return $value;
  }

  /**
   * Helper function to format the admin summary.
   */
  protected function valueFormatter($value) {
    if (empty($value)) {
      if ($value == 0) {
        $output = $this->t('Fresher');
      }
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
      if ($year && $month) {
        $output = $this->t('@year Year(s) @month Month(s)', ['@year' => $year, '@month' => $month]);
      }
      elseif ($year) {
        $output = $this->t('@year Year(s)', ['@year' => $year]);
      }
      elseif ($month) {
        $output = $this->t('@month Month(s)', ['@month' => $month]);
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = $options[$this->operator];
    if (in_array($this->operator, $this->operatorValues(2))) {
      $output .= ' ' . $this->t('@min and @max', ['@min' => $this->valueFormatter($this->value['min']), '@max' => $this->valueFormatter($this->value['max'])]);
    }
    elseif (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ' . $this->valueFormatter($this->value['value']);
    }
    return $output;
  }

  /**
   * Do some minor translation of the exposed input.
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    $rc = parent::acceptExposedInput($input);

    // Rewrite the input value so that it's in the correct format so that
    // the parent gets the right data.
    $value = $this->getFilterValue($input);

    $this->value = $value;
    $this->value = [
      'value' => $value,
    ];

    return $rc;
  }

}
