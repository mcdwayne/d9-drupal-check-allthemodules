<?php

namespace Drupal\taxonomy_moderator\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'taxonomy_moderator_widget' widget.
 *
 * @FieldWidget(
 *   id = "taxonomy_moderator_widget",
 *   label = @Translation("Taxonomy moderator widget"),
 *   field_types = {
 *     "taxonomy_moderator_field"
 *   }
 * )
 */
class TaxonomyModeratorWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->multiple = 1;

    $elements['status'] = [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->status) ? $items[$delta]->status : 0,
    ];

    $elements['last_edited_uid'] = [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->last_edited_uid) ? $items[$delta]->last_edited_uid : 0,
    ];

    $elements['value'] = [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#multiple' => 1,
      '#attributes' => (isset($items[$delta]->status) && $items[$delta]->status != 0) ? ['disabled' => 'disabled'] : '',
    ];

    $element = $element + $elements;

    return $element;
  }

}
