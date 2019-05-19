<?php

namespace Drupal\webform_select_collection\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_select_collection' element.
 *
 * @WebformElement(
 *   id = "webform_select_collection",
 *   label = @Translation("Select collection"),
 *   description = @Translation("Provides a form element for a table with selects or checkboxes in right column."),
 *   category = @Translation("Options elements"),
 *   composite = TRUE,
 * )
 */
class WebformSelectCollection extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      // Table settings.
      'line_items' => [],
      'line_options' => [],
      'js_select' => TRUE,
      'multiple' => TRUE,
      // @todo Handle checkboxes
    ] + parent::getDefaultProperties();

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];
    // @todo Handle checkboxes
    $type = ($this->hasMultipleValues($element) ? $this->t('Checkbox') : $this->t('Select'));

    $selectors = [];
    foreach ($element['#line_options'] as $value => $text) {
      if (is_array($text)) {
        $text = $value;
      }
      $selectors[":input[name=\"{$name}[{$value}]\"]"] = $text . ' [' . $type . ']';
    }
    return [$title => $selectors];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['line_items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element line items'),
      '#open' => TRUE,
    ];
    $form['line_items']['line_items'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Line items'),
      '#required' => TRUE,
      '#options_description' => FALSE,
    ];

    $form['line_items']['js_select'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all'),
      '#description' => $this->t('If checked, a select all select/checkbox will be added to the header.'),
      '#return_value' => TRUE,
    ];

    $form['line_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element line item options'),
      '#open' => TRUE,
    ];
    $form['line_options']['line_options'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Item options'),
      '#required' => TRUE,
      '#options_description' => FALSE,
    ];

    // Avoid confusion with "Allowed number of values" as it depends on options
    // configuration.
    $form['element']['multiple']['#disabled'] = TRUE;

    $form['display']['item']['#states']['invisible'] = [
      ':input[name="properties[format_items]"]' => ['value' => 'table'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Add missing element class.
    $element['#attributes']['class'][] = str_replace('_', '-', $element['#type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormats() {
    return [
      'ol' => $this->t('Ordered list'),
      'ul' => $this->t('Unordered list'),
      'hr' => $this->t('Horizontal rule'),
      'table' => $this->t('Table'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (!isset($options['delta'])) {
      $options['delta'] = 0;
    }

    if (is_numeric($options['delta'])) {
      $value = parent::getValue($element, $webform_submission, $options);
    }
    else {
      $value = parent::getValue($element, $webform_submission, ['delta' => 0]);
      $value = isset($value[$options['delta']]) ? $value[$options['delta']] : NULL;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $key = $options['delta'];
    $value = $this->getValue($element, $webform_submission, $options);
    $format = $this->getItemFormat($element);
    $format_raw = ($format === 'raw');

    if (!$format_raw) {
      $flattened_options = OptGroup::flattenOptions($element['#line_options']);
      $flattened_items = OptGroup::flattenOptions($element['#line_items']);
    }

    if ($format_raw) {
      $placeholders = [
        '@key' => $key,
        '@value' => $value,
      ];
    }
    else {
      $placeholders = [
        '@key' => WebformOptionsHelper::getOptionText($key, $flattened_items),
        '@value' => WebformOptionsHelper::getOptionText($value, $flattened_options),
      ];
    }

    return $this->t('@key:&nbsp;@value', $placeholders);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemsFormat($element);
    if ($format !== 'table') {
      return parent::formatHtmlItems($element, $webform_submission, $options);
    }
    $rows = [];

    $flattened_options = OptGroup::flattenOptions($element['#line_options']);
    $flattened_items = OptGroup::flattenOptions($element['#line_items']);

    $header = [$element['#title'], ''];
    $value = $this->getValue($element, $webform_submission, $options);
    foreach ($value as $item => $property) {
      $rows[] = [
        WebformOptionsHelper::getOptionText($item, $flattened_items),
        WebformOptionsHelper::getOptionText($property, $flattened_options),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'width' => '100%',
        'cellspacing' => 0,
        'cellpadding' => 5,
        'border' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $key = $options['delta'];
    $value = $this->getValue($element, $webform_submission, $options);
    $format = $this->getItemFormat($element);
    $format_raw = ($format === 'raw');

    if (!$format_raw) {
      $flattened_options = OptGroup::flattenOptions($element['#line_options']);
      $flattened_items = OptGroup::flattenOptions($element['#line_items']);
    }

    if ($format_raw) {
      $placeholders = [
        '@key' => $key,
        '@value' => $value,
      ];

      return $this->t('@key:@value', $placeholders);
    }
    else {
      $placeholders = [
        '@key' => WebformOptionsHelper::getOptionText($key, $flattened_items),
        '@value' => WebformOptionsHelper::getOptionText($value, $flattened_options),
      ];

      return $this->t('@key: @value', $placeholders);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemsFormat($element);
    if ($format === 'table') {
      $element['#format_items'] = 'hr';
    }
    return parent::formatTextItems($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    parent::initialize($element);
    // Set element items.
    if (isset($element['#line_items'])) {
      $element['#line_items'] = WebformOptions::getElementOptions($element, '#line_items');
    }
    // Set element options.
    if (isset($element['#line_options'])) {
      $element['#line_options'] = WebformOptions::getElementOptions($element, '#line_options');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $values = [];
    $keys = array_keys($element['#line_options']);
    $max = count($element['#line_options']);
    foreach (array_keys($element['#line_items']) as $item_name) {
      $index = rand(0, $max);
      $values[$item_name] = isset($keys[$index]) ? $keys[$index] : NULL;
    }

    return [$values];
  }

}
