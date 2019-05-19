<?php

namespace Drupal\telephone_type\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\telephone\Plugin\Field\FieldFormatter\TelephoneLinkFormatter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'telephone_type_link' formatter.
 *
 * @FieldFormatter(
 *   id = "telephone_type_link",
 *   label = @Translation("Telephone link w/type"),
 *   field_types = {
 *     "telephone_type"
 *   }
 * )
 */
class TelephoneTypeLinkFormatter extends TelephoneLinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => 'short',
      'location' => 'prefix',
      'separator' => ': ',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['label'] = [
      '#type' => 'select',
      '#title' => t('Label'),
      '#default_value' => $this->getSetting('label'),
      '#options' => ['key' => 'Short', 'value' => 'Description'],
      '#description' => t('Select to use either the key or value for the label.'),
    ];
    $elements['location'] = [
      '#type' => 'select',
      '#title' => t('Label location'),
      '#default_value' => $this->getSetting('location'),
      '#options' => ['prefix' => 'Before', 'suffix' => 'After'],
      '#description' => t('Select the location of the label.'),
    ];

    $elements['separator'] = [
      '#type' => 'textfield',
      '#title' => t('Separator'),
      '#default_value' => $this->getSetting('separator'),
      '#size' => 15,
      '#maxlength' => 10,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = 'Label: ' . ($this->getSetting('label') == 'key' ? 'Short' : 'Description');
    $summary[] = 'Label location: ' . ($this->getSetting('location') == 'prefix' ? 'Before' : 'After');
    $summary[] = 'Separator: ' . HTML::escape($this->getSetting('separator'));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $title_setting = $this->getSetting('title');
    $validator = \Drupal::service('telephone_type.validator');

    foreach ($items as $delta => $item) {
      // If type is fax, use markup to display.
      if (isset($item->type) && $item->type == 'fax') {
        $element[$delta]['#markup'] = $validator->format($item->value);
      }
      else {
        $element[$delta] = [
          '#type' => 'link',
          // Use custom title if available, otherwise use the telephone number.
          '#title' => $title_setting ?: $validator->format($item->value),
          '#url' => Url::fromUri($validator->formatUri($item->value)),
          '#options' => ['external' => TRUE],
        ];
      }

      if (!empty($item->_attributes)) {
        $element[$delta]['#options'] += ['attributes' => []];
        $element[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }

      // If label selected.
      if (!empty($item->type)) {
        $label = ($this->getSetting('label') == 'key') ? $item->type : telephone_types_options($item->type);
        if ($this->getSetting('location') == 'prefix') {
          $element[$delta]['#prefix'] = $label . HTML::escape($this->getSetting('separator'));
        }
        else {
          $element[$delta]['#suffix'] = HTML::escape($this->getSetting('separator')) . $label;
        }
      }
    }

    return $element;
  }

}
