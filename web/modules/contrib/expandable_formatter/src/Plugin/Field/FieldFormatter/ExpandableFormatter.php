<?php

namespace Drupal\expandable_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'expandable formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "expandable_formatter",
 *   label = @Translation("Expandable"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string_long",
 *   },
 * )
 */
class ExpandableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default = [
      'collapsed_height' => 20,
      'use_ellipsis' => TRUE,
      'effect' => 'slide',
      'trigger_expanded_label' => t('Expand'),
      'trigger_collapsed_label' => t('Collapse'),
      'trigger_classes' => 'button',
      'js_duration' => 500,
    ];
    return $default + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = [];
    $element['collapsed_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Collapsed height'),
      '#description' => $this->t('The number of pixels high that should be shown when the text is collapsed.'),
      '#default_value' => $this->getSetting('collapsed_height'),
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];
    $element['use_ellipsis'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Append ellipsis'),
      '#default_value' => $this->getSetting('use_ellipsis'),
    ];
    $element['trigger_expanded_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger expanded label'),
      '#default_value' => $this->getSetting('trigger_expanded_label'),
      '#required' => TRUE,
    ];
    $element['trigger_collapsed_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger collapsed label'),
      '#default_value' => $this->getSetting('trigger_collapsed_label'),
    ];
    $element['trigger_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger classes'),
      '#description' => $this->t('Provide additional CSS classes separated by spaces.'),
      '#default_value' => $this->getSetting('trigger_classes'),
    ];
    $element['effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation effect'),
      '#default_value' => $this->getSetting('effect'),
      '#options' => [
        'none' => $this->t('None'),
        'slide' => $this->t('Slide'),
      ],
      '#required' => TRUE,
    ];
    $element['js_duration'] = [
      '#title' => $this->t('Animation duration'),
      '#type' => 'number',
      '#description' => $this->t('The number of milliseconds that the animation should last.'),
      '#default_value' => $this->getSetting('js_duration'),
      '#min' => 1,
      '#step' => 1,
    ];

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary['collapsed_height'] = $this->t('Trim height: @collapsed_height', ['@collapsed_height' => $this->getSetting('collapsed_height')]);
    $summary['effect'] = $this->t('Effect: @effect', ['@effect' => $this->getSetting('effect')]);
    $summary['trigger_expanded_label'] = $this->t('Expand Label: @trigger_expanded_label', ['@trigger_expanded_label' => $this->getSetting('trigger_expanded_label')]);
    $summary['trigger_classes'] = $this->t('Trigger Class: @trigger_classes', ['@trigger_classes' => $this->getSetting('trigger_classes')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $attributes = [];
    $attributes['class'] = ['expandable-formatter'];
    if (!empty($this->getSetting('effect'))) {
      $attributes['data-effect'] = $this->getSetting('effect');
    }
    if (!empty($this->getSetting('collapsed_height'))) {
      $attributes['data-collapsed-height'] = $this->getSetting('collapsed_height');
    }
    if (!empty($this->getSetting('trigger_collapsed_label'))) {
      $attributes['data-collapsed-label'] = $this->getSetting('trigger_collapsed_label');
    }
    if (!empty($this->getSetting('trigger_expanded_label'))) {
      $attributes['data-expanded-label'] = $this->getSetting('trigger_expanded_label');
    }
    if (!empty($this->getSetting('js_duration'))) {
      $attributes['data-js-duration'] = $this->getSetting('js_duration');
    }
    $triggerClasses = '';
    if (!empty($this->getSetting('trigger_classes'))) {
      $triggerClasses = $this->getSetting('trigger_classes');
    }
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'expandable_formatter',
        '#attributes' => $attributes,
        '#content' => $item->value,
        '#trigger_classes' => $triggerClasses,
        '#use_ellipsis' => $this->getSetting('use_ellipsis'),
        '#attached' => [
          'library' => [
            'expandable_formatter/expand',
          ],
        ],
      ];
    }
    return $elements;
  }

}
