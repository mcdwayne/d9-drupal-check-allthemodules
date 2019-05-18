<?php

namespace Drupal\delimited_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'delimited_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "delimited_field_formatter",
 *   label = @Translation("Delimited field formatter"),
 *   field_types = {
 *     "list_integer"
 *   }
 * )
 */
class DelimitedFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];

    // Fall back to field settings by default.
    $settings['field_delimiter'] = '>';
    $settings['display_chunks'] = '1';
    $settings['display_type'] = 'delimited_text';
    $settings['display_delimiter'] = '';
    $settings['display_html_list_type'] = 'ul';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $field_name = $this->fieldDefinition->getName();

    $form['field_delimiter'] = [
      '#title' => $this->t('Field Delimiter'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('field_delimiter'),
      '#required' => TRUE,
    ];
    $form['display_chunks'] = [
      '#title' => $this->t('Display Chunks'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('display_chunks'),
      '#description' => t('Comma delimited list of chunks to display. Leave empty to display all.'),
    ];
    $form['display_type'] = [
      '#title' => $this->t('Display Type'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('display_type'),
      '#options' => [
        'delimited_text' => t('Delimited Text'),
        'html_list' => t('HTML List'),
      ],
      '#required' => TRUE,
    ];
    $form['display_delimiter'] = [
      '#title' => $this->t('Display Delimiter'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('display_delimiter'),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][display_type]"]' => ['value' => 'delimited_text'],
        ],
      ],
    ];
    $form['display_html_list_type'] = [
      '#title' => $this->t('HTML List Type'),
      '#type' => 'select',
      '#options' => ['ul' => t('Unordered List'), 'ol' => t('Ordered List')],
      '#default_value' => $this->getSetting('display_html_list_type'),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][display_type]"]' => ['value' => 'html_list'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Field Delimiter: @field_delimiter', ['@field_delimiter' => $this->getSetting('field_delimiter')]);
    $summary[] = t('Display Chunks: @display_chunks', ['@display_chunks' => $this->getSetting('display_chunks')]);
    $summary[] = t('Display Type: @display_type', ['@display_type' => $this->getSetting('display_type')]);
    if ($this->getSetting('display_type') == 'delimited_text') {
      $summary[] = t('Display Delimiter: @display_delimiter', ['@display_delimiter' => $this->getSetting('display_delimiter')]);
    }
    if ($this->getSetting('display_type') == 'html_list') {
      $summary[] = t('HTML List Type: @display_html_list_type', ['@display_html_list_type' => $this->getSetting('display_html_list_type')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $settings = $this->getSettings();

    // Cover list_integer field type case.
    if ($items->getFieldDefinition()->getType() == 'list_integer') {
      if ($items->count()) {
        $provider = $items->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getOptionsProvider('value', $items->getEntity());
        // Flatten the possible options, to support opt groups.
        $options = OptGroup::flattenOptions($provider->getPossibleOptions());

        foreach ($items as $delta => $item) {
          $value = $item->value;
          // If the stored value is in the current set of allowed values,
          // display the associated label, otherwise just display the raw value.
          $field_text = isset($options[$value]) ? $options[$value] : $value;

          // Process output.
          if (empty($settings['display_chunks'])) {
            $chunks = explode($settings['field_delimiter'], $field_text);
          }
          else {
            $text_chunks = explode($settings['field_delimiter'], $field_text);
            $display_chunks = explode(',', $settings['display_chunks']);
            $chunks = [];
            foreach ($text_chunks as $key => $chunk) {
              if (in_array($key + 1, $display_chunks)) {
                $chunks[$key + 1] = $chunk;
              }
            }
          }

          switch ($settings['display_type']) {
            case 'delimited_text':
              $elements[$delta] = [
                '#markup' => implode($settings['display_delimiter'], $chunks),
                '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
              ];
              break;

            case 'html_list':
              $elements[$delta] = [
                '#theme' => 'item_list',
                '#items' => $chunks,
                '#list_type' => $settings['display_html_list_type'],
              ];
              break;
          }

        }
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
