<?php

/**
 * @file
 * Contains \Drupal\entity_reference_formatter\Plugin\Field\FieldFormatter\EntityReferenceFormatter.
 */

namespace Drupal\entity_reference_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * EntityReference formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_formatter",
 *   label = @Translation("Entity Reference Formatter"),
 *   weight = 100,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceFormatter extends FormatterBase {

  /**
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->formatterManager = \Drupal::service('plugin.manager.field.formatter');
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $settings = $this->getSettings();
    $name = $settings['formatters']['status']['listing'];
    $options = $settings['formatters'][$name];

    // Merge defaults from the formatters and ensure proper ordering.
    $this->prepareFormatters($this->fieldDefinition->getType(), $settings['formatters']);

    $formatter_instance = $this->getFormatter($options);
    $formatter_instance->prepareView(array($items->getEntity()->id() => $items));

    $result = $formatter_instance->viewElements($items, $langcode);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $formatters = $settings['formatters'];
    $this->prepareFormatters($this->fieldDefinition->getType(), $formatters, FALSE);

    $elements['#attached']['library'][] = 'entity_reference_formatter/admin';

    $parents = array('fields', $this->fieldDefinition->getName(), 'settings_edit_form', 'settings', 'formatters');

    // Filter status.

    $elements['formatters']['status'] = array(
      '#type' => 'item',
      '#title' => t('Enabled formatters'),
      '#prefix' => '<div class="entity-reference-formatter-status-wrapper">',
      '#suffix' => '</div>',
    );
    $formatter_listing = array();
    foreach ($formatters as $name => $options) {
      $formatter_listing[$name] = $options['label'];
    }

    $elements['formatters']['status']['listing'] = array(
      '#type' => 'radios',
      '#default_value' => isset($settings['formatters']['status']['listing']) ? $settings['formatters']['status']['listing'] :'',
      '#options' => $formatter_listing,
    );

    // Filter settings.
    foreach ($formatters as $name => $options) {
      $formatter_instance = $this->getFormatter($options);
      $settings_form = $formatter_instance->settingsForm($form, $form_state);

      if (!empty($settings_form)) {
        $elements['formatters']['settings'][$name] = array(
          '#type' => 'fieldset',
          '#title' => $options['label'],
          '#parents' => array_merge($parents, array($name, 'settings')),
          '#weight' => $options['weight'],
          '#group' => 'formatter_settings',
        );
        $elements['formatters']['settings'][$name] += $settings_form;
      }

      $elements['formatters']['settings'][$name]['formatter'] = array(
        '#type' => 'value',
        '#value' => $name,
        '#parents' => array_merge($parents, array($name, 'formatter')),
      );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $name = $settings['formatters']['status']['listing'];
    $options = $settings['formatters'][$name];
    $formatters = $this->formatterManager->getDefinitions();
    $this->prepareFormatters($this->fieldDefinition->getType(), $settings['formatters']);

    $summary_items = array();
    $field_settings = $this->fieldDefinition->getFieldStorageDefinition()->getSettings();
    if (!isset($formatters[$name])) {
      $summary_items[] = t('Unknown formatter %name.', array('%name' => $name));
    }
    elseif (!in_array($field_settings['target_type'], $formatters[$name]['field_types'])) {
      $summary_items[] = t('Selected formatter %name .', array('%name' => $formatters[$name]['label']));
    }
    else {
      $formatter_instance = $this->getFormatter($options);
      $result = $formatter_instance->settingsSummary();

      $summary_items[] = SafeMarkup::format('<strong>@label</strong>!settings_summary', array(
                           '@label' => $formatter_instance->getPluginDefinition()['label'],
                           '!settings_summary' => '<br>' . Xss::filter(!empty($result) ? implode(', ', $result) : ''),
                         ));
    }
    if (empty($summary_items)) {
      $summary = array(
        '#markup' => t('No formatters selected yet.'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      );
    }
    else {
      $summary = array(
        '#theme' => 'item_list',
        '#items' => $summary_items,
        '#type' => 'ol'
      );
    }

    return array(drupal_render($summary));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'formatters' => array(),
    );
  }

  /**
   * Gets an instance of a formatter.
   *
   * @param array $options
   *   Formatter options.
   *
   * @return \Drupal\Core\Field\FormatterInterface
   */
  protected function getFormatter($options) {
    if (!isset($options['settings'])) {
      $options['settings'] = array();
    }

    $options += array(
      'field_definition' => $this->fieldDefinition,
      'view_mode' => $this->viewMode,
      'configuration' => array('type' => $options['id'], 'settings' => $options['settings']),
    );

    return $this->formatterManager->getInstance($options);
  }

  /**
   * Decorates formatters definitions to be complete for plugin instantiation.
   *
   * @param string $field_type
   *   The field type for which to prepare the formatters.
   * @param array $formatters
   *   The formatter definitions we want to prepare.
   * @param bool $filter_enabled
   *   If TRUE (default) will filter out any disabled formatters. If FALSE
   *   will return all possible formatters.
   *
   * @todo - this might be merged with getFormatter()?
   */
  protected function prepareFormatters($field_type, array &$formatters, $filter_enabled = TRUE) {
    $default_weight = 0;

    $allowed_formatters = $this->getPossibleFormatters($field_type);
    $formatters += $allowed_formatters;

    $formatters = array_intersect_key($formatters, $allowed_formatters);

    foreach ($formatters as $formatter => $info) {
      // Remove disabled formatters.
      if ($filter_enabled && empty($info['status'])) {
        unset($formatters[$formatter]);
        continue;
      }

      // Provide some default values.
      $formatters[$formatter] += array('weight' => $default_weight++);
      // Merge in defaults.
      $formatters[$formatter] += $allowed_formatters[$formatter];
      if (!empty($allowed_formatters[$formatter]['settings'])) {
        $formatters[$formatter]['settings'] += $allowed_formatters[$formatter]['settings'];
      }
    }

    // Sort by weight.
    uasort($formatters, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
  }

  /**
   * Gets possible formatters for the given field type.
   *
   * @param string $field_type
   *   Field type for which we want to get the possible formatters.
   *
   * @return array
   *   Formatters info array.
   */
  protected function getPossibleFormatters($field_type) {
    $return = array();
    $settings = $this->fieldDefinition->getFieldStorageDefinition()->getSettings();
    foreach (\Drupal::service('plugin.manager.field.formatter')->getDefinitions() as $formatter => $info) {
      if ($formatter == 'entity_reference_formatter') {
        continue;
      }
      if (isset($settings['target_type']) && in_array($settings['target_type'], $info['field_types'])) {
        $return[$formatter] = $info;
      }
    }

    return $return;
  }
}
