<?php

namespace Drupal\masked_output\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'mask output string' formatter.
 *
 * @FieldFormatter(
 *   id = "masked_output",
 *   label = @Translation("Mask Output"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class MaskOutputFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->field_definition = $field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'mask_type' => 'show_characters',
      'character_count' => 3,
      'orientation' => 'end',
      'mask_symbol' => '*',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $max_length = $this->field_definition->getSettings()['max_length'];
    $form['mask_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Mask type'),
      '#default_value' => $this->getSetting('mask_type'),
      '#description' => $this->t('Type of masking.'),
      '#options' => $this->maskOutputMaskTypeOptions(),
    ];
    $form['character_count'] = [
      '#type' => 'number',
      '#size' => 3,
      '#min' => 1,
      '#max' => $max_length,
      '#title' => $this->t('Characters count'),
      '#default_value' => $this->getSetting('character_count'),
      '#description' => $this->t('Number of characters to show/mask from 1 to @max_length.', ['@max_length' => $max_length]),
      '#required' => TRUE,
    ];
    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#default_value' => $this->getSetting('orientation'),
      '#options' => $this->maskOutputOrientationOptions(),
      '#description' => $this->t('Specifies where masking should start from.'),
    ];
    $form['mask_symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Masker'),
      '#description' => $this->t('Special character used to replace the characters. (Use special characters only, accepts only one value)'),
      '#default_value' => $this->getSetting('mask_symbol'),
      '#size' => 3,
      '#maxlength' => 1,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $mask_type = $this->getSetting('mask_type');
    $summary[] = $this->t('Mask type: @mask_type', ['@mask_type' => $this->maskOutputMaskTypeOptions($mask_type)]);
    $summary[] = $this->t('Characters count: @character_count', ['@character_count' => $this->getSetting('character_count')]);
    $orientation = $this->getSetting('orientation');
    $summary[] = $this->t('Orientation: @orientation', ['@orientation' => $this->maskOutputOrientationOptions($orientation)]);
    $summary[] = $this->t('Masker: @mask_symbol', ['@mask_symbol' => $this->getSetting('mask_symbol')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $view_value = $this->viewValue($item);
      $elements[$delta] = $view_value;
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    $mask_type = $this->getSetting('mask_type');
    $character_count = $this->getSetting('character_count');
    $orientation = $this->getSetting('orientation');
    $mask_symbol = $this->getSetting('mask_symbol');
    $text = $item->value;
    $text_len = strlen($text);
    $start = 0;
    $end = 0;
    if ($text_len > $character_count) {
      // Show number of characters.
      if ($mask_type == 'show_characters') {
        if ($orientation == 'begin') {
          $start = $character_count;
        }
        else {
          $end = $character_count;
        }
        $value = substr($text, 0, $start) . str_repeat($mask_symbol, $text_len - $character_count) . substr($text, $text_len - $end, $end);
      }
      // Masking number of characters.
      elseif ($mask_type == 'mask_characters') {
        if ($orientation == 'begin') {
          $start = $character_count;
        }
        else {
          $end = $character_count;
        }
        $value = str_repeat($mask_symbol, $start) . substr($text, $start - $end - $text_len) . str_repeat($mask_symbol, $end);
      }
    }
    else {
      $value = $item->value;
    }
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => $value],
    ];
  }

  /**
   * Gets Orientation options.
   *
   * @param $key
   *   Option key value.
   *
   * @return array
   *   The orientation select list options.
   */

  protected function maskOutputOrientationOptions($key = NULL) {
    $options = [
      'end' => $this->t('From Ending'),
      'begin' => $this->t('From Beginning'),
    ];
    return $key == NULL ? $options : $options[$key];
  }

  /**
   * Gets mask type options.
   *
   * @param $key
   *   Option key value.
   *
   * @return array
   *   Mask type select list options list.
   */
  protected function maskOutputMaskTypeOptions($key = NULL) {
    $options = [
      'show_characters' => 'Show characters',
      'mask_characters' => 'Mask characters',
    ];
    return $key == NULL ? $options : $options[$key];
  }

}
