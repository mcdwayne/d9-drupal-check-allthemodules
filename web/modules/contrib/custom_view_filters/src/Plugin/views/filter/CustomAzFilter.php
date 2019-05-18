<?php

namespace Drupal\custom_view_filters\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Filters by first letter of an string field.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("custom_az_filter")
 */
class CustomAzFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function canBuildGroup() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);

    $filter_id = $this->getFilterId();
    $type = $this->options['expose']['multiple'] ? 'checkboxes' : 'radios';

    $form[$filter_id] = [
      '#type' => $type,
      '#options' => $this->getAzOptions(),
    ];

    $this->valueForm($form, $form_state);
  }

  /**
   * This method returns the ID of the fake field which contains this plugin.
   *
   * It is important to put this ID to the exposed field of this plugin for the following reasons:
   * a) To avoid problems with FilterPluginBase::acceptExposedInput function
   * b) To allow this filter to be printed on twig templates with {{ form.custom_az_filter }}
   *
   * @return string
   *   ID of the field which contains this plugin.
   */
  private function getFilterId() {
    return $this->options['expose']['identifier'];
  }

  /**
   * It generates all the letters of the alphabet.
   *
   * @return array
   *   Array with all letters indexed by the letters itself.
   */
  private function getAzOptions() {
    $return = [];
    $lletres = range('A', 'Z');

    foreach ($lletres as $lletra) {
      $return[$lletra] = $lletra;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    if (!$this->options['exposed']) {
      // Administrative value.
      $this->queryFilterByLetterMultipleValues($this->options['az_field_name'], $this->options['az_letter']);
    }
    else {
      // Exposed value.
      $type = $this->options['expose']['multiple'] ? 'multiple' : 'single';

      if ($type == 'single') {
        // To let single value use the same methods as multiple values.
        $this->value = [$this->value[0] => $this->value[0]];
      }

      $this->queryFilterByLetterMultipleValues($this->options['az_field_name'], $this->value);
    }
  }

  /**
   * Filter by letter and specified field.
   *
   * @param string $fieldName
   *   Machine name of the field.
   * @param array $lletresSeleccionades
   *   Selected letter.
   */
  private function queryFilterByLetterMultipleValues($fieldName, $lletresSeleccionades) {
    // We call the method defined at the operators function.
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method'] . 'Multiple'}($fieldName, $lletresSeleccionades);
    }
  }

  /**
   * We filter by the first letter of the first word.
   *
   * @param string $fieldName
   *   Machine name of the field.
   * @param array $lletresSeleccionades
   *   Selected letter.
   */
  private function opFirstWordMultiple($fieldName, $lletresSeleccionades) {
    if (!empty($lletresSeleccionades)) {
      $this->query->setWhereGroup('OR', 7);
      $this->query->addTable("node__{$fieldName}");
      foreach ($lletresSeleccionades as $key => $value) {
        // IMPORTANT: The three equals comparison is necessary.
        if ($key === $value) {
          $this->query->addWhere(7, "node__{$fieldName}.{$fieldName}_value", $this->securityFilter($value) . '%', 'LIKE');
        }
      }
    }
  }

  /**
   * We filter by the first letter of the second word.
   *
   * @param string $fieldName
   *   Machine name of the field.
   * @param array $lletresSeleccionades
   *   Selected letter.
   */
  private function opSecondWordMultiple($fieldName, $lletresSeleccionades) {
    if (!empty($lletresSeleccionades)) {
      $this->query->setWhereGroup('OR', 7);
      $this->query->addTable("node__{$fieldName}");
      foreach ($lletresSeleccionades as $key => $value) {
        // IMPORTANT: The three equals comparison is necessary.
        if ($key === $value) {
          $this->query->addWhere(7, "node__{$fieldName}.{$fieldName}_value", '% ' . $this->securityFilter($value) . '%', 'LIKE');
        }
      }
    }
  }

  /**
   * Security filter.
   *
   * @param mixed $value
   *   Input.
   *
   * @return mixed
   *   Sanitized value of input.
   */
  private function securityFilter($value) {
    $value = Html::escape($value);
    $value = Xss::filter($value);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (!$this->options['exposed']) {
      $form['az_letter'] = [
        '#type' => 'select',
        '#title' => $this->t('Begin with following letter'),
        '#options' => $this->getAzOptions(),
        '#default_value' => isset($this->options['az_letter']) ? $this->options['az_letter'] : NULL,
        '#multiple' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator'] = ['default' => 'first_word_check'];
    $options['az_field_name'] = ['default' => ''];
    $options['az_letter'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['az_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use Custom AZ filter with this field name (enter machine name)'),
      '#description' => $this->t('Machine field names appear on content types field list (e.g. field_fullname)'),
      '#default_value' => isset($this->options['az_field_name']) ? $this->options['az_field_name'] : NULL,
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $operator = '';
    $info = $this->operators();
    if (!empty($info[$this->operator]['title'])) {
      $operator = $info[$this->operator]['title'];
    }

    $multiple = $this->options['expose']['multiple'] ? $this->t('Multiple') : $this->t('Single');

    // Exposed filter.
    if ($this->options['exposed']) {
      $variables = [
        '@field' => $this->options['az_field_name'],
        '@op' => $operator,
        '@multiple' => $multiple,
      ];
      return $this->t('Exposed on field "@field" - @op - @multiple', $variables);
    }

    // Administrative filter.
    $variables = [
      '@letter' => $this->options['az_letter'],
      '@field' => $this->options['az_field_name'],
      '@op' => $operator,
    ];
    return $this->t('Begin with "@letter" on field "@field" - @op', $variables);
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      'first_word_check' => [
        'title' => $this->t('First word check'),
        'method' => 'opFirstWord',
        'short' => $this->t('FW'),
        'values' => 1,
      ],
      'second_word_check' => [
        'title' => $this->t('Second word check'),
        'method' => 'opSecondWord',
        'short' => $this->t('SW'),
        'values' => 1,
      ],
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

}
