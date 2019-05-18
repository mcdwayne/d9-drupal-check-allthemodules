<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementation of the 'file with class' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_label_class",
 *   label = @Translation("Label (with class)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLabelClassFormatter extends EntityReferenceLabelFormatter {

  use ElementClassTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = parent::defaultSettings() + [
      'tag' => '',
    ];

    return ElementClassTrait::elementClassDefaultSettings($default_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $class = $this->getSetting('class');

    $wrapper_options = [
      'span' => 'span',
      'div' => 'div',
      'p' => 'p',
    ];
    foreach (range(1, 5) as $level) {
      $wrapper_options['h' . $level] = 'H' . $level;
    }

    $elements['tag'] = [
      '#title' => $this->t('Tag'),
      '#type' => 'select',
      '#options' => $wrapper_options,
      '#default_value' => $this->getSetting('tag'),
      '#description' => 'If not linked, set which tag should be used as the wrapper with the class.',
      '#states' => [
        'visible' => [
          ':input[name$="[link]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $this->elementClassSettingsForm($elements, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $class = $this->getSetting('class');
    if ($tag = $this->getSetting('tag')) {
      $summary[] = $this->t('Tag: @tag', ['@tag' => $tag]);
    }

    return $this->elementClassSettingsSummary($summary, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $class = $this->getSetting('class');

    foreach ($items as $delta => $item) {
      // If it's a link add the class.
      if (isset($elements[$delta]['#type']) && $elements[$delta]['#type'] === 'link') {
        if (!empty($class)) {
          $elements[$delta]['#options']['attributes']['class'][] = $class;
        }
      }
      else {
        // Otherwise render as a div.
        $attributes = new Attribute();
        if (!empty($class)) {
          $attributes->addClass($class);
        }

        // Otherwise collect the info needed for new render.
        $label = $elements[$delta]['#plain_text'];
        $cache = $elements[$delta]['#cache'];

        $elements[$delta] = [
          '#type' => 'html_tag',
          '#tag' => $this->getSetting('tag'),
          '#attributes' => $attributes->toArray(),
          '#value' => $label,
          '#cache' => $cache,
        ];
      }
    }

    return $elements;
  }

}
