<?php

namespace Drupal\location_selector\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * My custom location_selector filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("location_selector_filter")
 */
class LocationSelectorFilter extends FilterPluginBase {

  /**
   * All words separated by spaces or sentences encapsulated by double quotes.
   */
  const WORDS_PATTERN = '/ (-?)("[^"]+"|[^" ]+)/i';

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['required'] = ['default' => FALSE];
    $options['expose']['contains']['placeholder'] = ['default' => ''];
    $options['expose']['contains']['field_widget'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['placeholder'] = NULL;
    $this->options['expose']['field_widget'] = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['placeholder'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['placeholder'],
      '#title' => $this->t('Placeholder'),
      '#size' => 40,
      '#description' => $this->t('Hint text that appears inside the field when empty.'),
    ];
    $form['expose']['field_widget'] = [
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['field_widget'],
      '#title' => $this->t('Get display settings'),
      '#size' => 40,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'entity_type;bundle;form_mode',
      ],
      '#description' => $this->t('Fill in the entity_type, bundle and form_mode in this order with semicolon between: entity_type;bundle;form_mode.<br>For example: node;article;default'),
    ];

    if (!empty($field_widget_settings = $this->options['expose']['field_widget'])) {

      $wrapper_class = 'ls--field--type-location-selector-type';
      $form_type = 'ls--views-exposed-option-form';

      $prefix = '';
      $suffix = '';
      if (!empty($form['value']['#prefix']) && !empty($form['value']['#suffix'])) {
        $prefix = $form['value']['#prefix'];
        $suffix = $form['value']['#suffix'];
      }
      $form['value']['#prefix'] = $prefix . '<div class="' . $wrapper_class . ' ' . $form_type . '">';
      $form['value']['#suffix'] = '</div>' . $suffix;

      // Get widget.
      $widet_data = explode(';', $field_widget_settings);
      $form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->load($widet_data[0] . '.' . $widet_data[1] . '.' . $widet_data[2]);
      // Get field name.
      $ls_table_field = explode('__', $this->table);
      $ls_field = $ls_table_field[1];
      // Get widget settings.
      $widget_types = $form_display->getComponents();
      $ls_widget = $widget_types[$ls_field];
      $ls_widget_settings = $ls_widget['settings'];

      if (empty($form['#attached'])) {
        $form['#attached'] = [];
      }
      $form['#attached'] = array_merge($form['#attached'], [
        'library' => ['location_selector/location_selector.form'],
        'drupalSettings' => [
          'location_selector' => [
            'form_element_settings' => $ls_widget_settings,
            'form_element_default_values' => $this->value,
            'form_type' => $form_type,
            'form_wrapper_class' => $wrapper_class,
          ],
        ],
      ]);

    }
  }

  /**
   * Initialize the operators.
   *
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  public function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'short' => $this->t('!='),
        'method' => 'opEqual',
        'values' => 1,
      ],
    ];
    // If the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'not empty' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  /**
   * Build strings from the operators() for 'select' options.
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Return admin summary.
   */
  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = '';
    if (!empty($options[$this->operator])) {
      $output = $options[$this->operator];
    }
    if (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ' . $this->value;
    }
    return $output;
  }

  /**
   * Return options.
   */
  protected function operatorValues($values = 1) {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * Provide a simple textfield for equality.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    $wrapper_class = 'ls--field--type-location-selector-type';
    $form_type = 'ls--views-exposed-form';

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
        $which = in_array($this->operator, $this->operatorValues(1)) ? 'value' : 'none';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    if ($which == 'all' || $which == 'value') {
      $form['value'] = [
        '#type' => 'textarea',
        '#prefix' => '<div class="' . $wrapper_class . ' ' . $form_type . '">',
        '#suffix' => '</div>',
        '#title' => $this->t('Value'),
        '#rows' => 5,
        '#attributes' => [
          'class' => [
            'js-text-full',
            'text-full',
          ],
          'readonly' => 'readonly',
        ],
        '#default_value' => $this->value,
      ];
      if (!empty($this->options['expose']['placeholder'])) {
        $form['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
      }
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }

      if ($which == 'all') {
        // Setup #states for all operators with one value.
        foreach ($this->operatorValues(1) as $operator) {
          $form['value']['#states']['visible'][] = [
            $source => ['value' => $operator],
          ];
        }
      }
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = [
        '#type' => 'value',
        '#value' => NULL,
      ];
    }

    if (!empty($field_widget_settings = $this->options['expose']['field_widget'])) {
      // Don't show on preview, specially the views preview, because there are
      // problems on views edit form because it conflicts with the
      // views exposed option form. (2 attachments to views)
      // @see buildExposeForm()
      $current_route_name = \Drupal::service('current_route_match')->getRouteName();
      if ($current_route_name !== 'entity.view.preview_form') {

        $default_value = $this->value;
        if (empty($default_value) && !empty($user_values = $form_state->getUserInput())) {
          $default_value = $user_values[$this->field];
        }

        // Get widget.
        $widet_data = explode(';', $field_widget_settings);
        $form_display = \Drupal::entityTypeManager()
          ->getStorage('entity_form_display')
          ->load($widet_data[0] . '.' . $widet_data[1] . '.' . $widet_data[2]);
        // Get field name.
        $ls_table_field = explode('__', $this->table);
        $ls_field = $ls_table_field[1];
        // Get widget settings.
        $widget_types = $form_display->getComponents();
        $ls_widget = $widget_types[$ls_field];
        $ls_widget_settings = $ls_widget['settings'];

        if (empty($form['#attached'])) {
          $form['#attached'] = [];
        }
        $form['#attached'] = array_merge($form['#attached'], [
          'library' => ['location_selector/location_selector.form'],
          'drupalSettings' => [
            'location_selector' => [
              'form_element_settings' => $ls_widget_settings,
              'form_element_default_values' => $default_value,
              'form_type' => $form_type,
              'form_wrapper_class' => $wrapper_class,
            ],
          ],
        ]);

      }
    }
    else {
      \Drupal::messenger()->addError('You must define the specific field widget in the view exposed Location Selector filter field.');
      unset($form['value']);
    }
  }

  /**
   * Return correct operator.
   */
  public function operator() {
    return $this->operator == '=' ? 'LIKE' : 'NOT LIKE';
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Filters by a regular expression.
   *
   * @param string $field
   *   The expression pointing to the queries field, for example "foo.bar".
   */
  public function opEqual($field) {
    $values = json_decode($this->value, TRUE);
    if (json_last_error() == JSON_ERROR_NONE) {
      if (!empty(($values['selected']))) {
        $one_or_all = 'one';
        $value = key($values['selected']);
        $query_string = 'JSON_CONTAINS_PATH(' . $field . ', \'' . $one_or_all . '\', \'$."selected"."' . $value . '"\')';
        $this->query->addWhereExpression($this->options['group'], $query_string);
      }
    }
  }

  /**
   * Filters by a regular expression.
   *
   * @param string $field
   *   The expression pointing to the queries field, for example "foo.bar".
   */
  protected function opEmpty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhere($this->options['group'], $field, NULL, $operator);
  }

}
