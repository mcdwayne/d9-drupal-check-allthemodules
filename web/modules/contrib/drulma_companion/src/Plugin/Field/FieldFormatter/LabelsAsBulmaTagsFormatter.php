<?php

namespace Drupal\drulma_companion\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'Labels as Bulma tags' formatter.
 *
 * @FieldFormatter(
 *   id = "drulma_entity_reference_label_tags",
 *   label = @Translation("Label as Bulma tag"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class LabelsAsBulmaTagsFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => '',
      'color' => '',
      'rounded' => FALSE,
      'inline' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size of the tags'),
      '#default_value' => $this->getSetting('size'),
      '#options' => [
        '' => $this->t('Default'),
        'normal' => $this->t('Normal'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
    ];
    $elements['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color of the tags'),
      '#default_value' => $this->getSetting('color'),
      '#options' => [
        '' => $this->t('Default'),
        'black' => $this->t('Black'),
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
        'white' => $this->t('White'),
        'primary' => $this->t('Primary'),
        'link' => $this->t('Link'),
        'info' => $this->t('Info'),
        'success' => $this->t('Success'),
        'warning' => $this->t('Warning'),
        'danger' => $this->t('Danger'),
      ],
    ];
    $elements['rounded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rounded tags'),
      '#default_value' => $this->getSetting('rounded'),
    ];
    $elements['inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show tags inline'),
      '#default_value' => $this->getSetting('inline'),
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Color: @color', ['@color' => $this->getSetting('color') ?: $this->t('Default')]);
    $summary[] = $this->t('Size: @size', ['@size' => $this->getSetting('size') ?: $this->t('Default')]);
    $summary[] = $this->t('Rounded: @rounded', ['@rounded' => $this->getSetting('rounded') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Show tags inline: @inline', ['@inline' => $this->getSetting('inline') ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);

    foreach ($element as $delta => &$item) {
      $classes = ['tag'];
      if ($this->getSetting('size')) {
        $classes[] = 'is-' . $this->getSetting('size');
      }
      if ($this->getSetting('color')) {
        $classes[] = 'is-' . $this->getSetting('color');
      }
      if ($this->getSetting('rounded')) {
        $classes[] = 'is-rounded';
      };
      if (($item['#type'] ?? '') === 'link') {
        if (!isset($item['#options']['attributes']['class'])) {
          $item['#options']['attributes']['class'] = [];
        }
        $item['#options']['attributes']['class'] += $classes;
      }
      else {
        $item['#prefix'] = '<span class="' . implode(' ', $classes) . '">';
        $item['#suffix'] = '</span>';
      }
    }

    if ($this->getSetting('inline')) {
      $element = [
        [
          '#type' => 'container',
          '#attributes' => ['class' => ['tags']],
          'element' => $element,
        ],
      ];
    }
    return $element;
  }

}
