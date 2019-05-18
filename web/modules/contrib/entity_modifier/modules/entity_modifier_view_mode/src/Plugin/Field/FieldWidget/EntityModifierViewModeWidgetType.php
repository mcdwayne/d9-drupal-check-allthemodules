<?php

namespace Drupal\entity_modifier_view_mode\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_modifier_view_mode_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "entity_modifier_view_mode_widget_type",
 *   label = @Translation("Entity modifier view mode widget type"),
 *   field_types = {
 *     "entity_modifier_view_mode"
 *   }
 * )
 */
class EntityModifierViewModeWidgetType extends WidgetBase {

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
    if (empty($form['#parent_entity'])) {
      return [];
    };

    $parent_entity = $form["#parent_entity"];

    $view_modes = \Drupal::service('entity_display.repository')
      ->getViewModes($parent_entity->getEntityTypeId());

    $options = [];
    foreach ($view_modes as $key => $view_mode) {
      $entity_display = EntityViewDisplay::load("{$parent_entity->getEntityTypeId()}.{$parent_entity->bundle()}.{$key}");
      if ($entity_display) {
        $options[$key] = $view_mode['label'];
      }
    }

    if (empty($options)) {
      return;
    }

    $element['value'] = $element + [
      '#type' => 'select',
      '#title' => t('View mode'),
      '#options' => $options,
      '#default_value' => $items[$delta]->getValue(),
      '#weight' => -1,
    ];

    return $element;
  }

}
