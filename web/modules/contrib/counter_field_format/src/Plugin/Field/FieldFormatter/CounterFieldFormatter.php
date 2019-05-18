<?php

namespace Drupal\counter_field_format\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'counter_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "counter_field_formatter",
 *   label = @Translation("Counter field formatter"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class CounterFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['duration'] = [
      '#title' => $this->t('Duration'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('duration'),
      '#description' => $this->t('Time within which the counting should be done'),
    ];
    $elements['easing_style'] = [
      '#title' => $this->t('Easing style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => ['Swing', 'Linear'],
      '#description' => $this->t('Animating style'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $duration = $this->getSetting('duration');
      $easing_style = $this->getSetting('easing_style');
      // Add default options if not specified.
      if (empty($duration)) {
        $duration = 400;
      }
      if (empty($easing_style)) {
        $easing_style = 'linear';
      }
      if (!empty($item->_attributes)) {
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
      $counter_attributes['duration'] = $duration;
      $counter_attributes['easing_style'] = $easing_style;
      $elements[$delta] = [
        '#theme' => 'counter_field_format',
        '#item' => $item,
        '#counter_attributes' => $counter_attributes,
        '#attached' => ['library' => ['counter_field_format/counter']],
      ];
      // Make the user opted values available to js.
      $elements[$delta]['#attached']['drupalSettings']['counter_attributes']['duration'] = $duration;
      $elements[$delta]['#attached']['drupalSettings']['counter_attributes']['easing_style'] = $easing_style;
    }

    return $elements;
  }

}
