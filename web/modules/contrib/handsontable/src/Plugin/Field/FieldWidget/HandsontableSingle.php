<?php

/**
 * @file
 * Contains \Drupal\handsontable\Plugin\Field\FieldWidget\HandsontableSingle.
 */

namespace Drupal\handsontable\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'handsontable_single' widget.
 *
 * @FieldWidget(
 *   id = "handsontable_single",
 *   label = @Translation("Handsontable - single"),
 *   field_types = {"handsontable_single"}
 * )
 */
class HandsontableSingle extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    $settings = [];

    return $settings + parent::defaultSettings();

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();

    $field_name = $this->fieldDefinition->getName();

    $element = [];
    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
  	$settings = $this->getSettings();
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings();

    $field_name = $this->fieldDefinition->getName();

    $item_id = Html::getUniqueId("ht-$field_name-$delta");

    $widget= ['#theme_wrappers' => ['form_element']];
    $widget['container'] = [
      '#type' => 'item',
      '#markup' => '<div class="handsontable-widget" data-target="' . $item_id . '"></div>'
    ];
    $widget['value'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => [$item_id]],
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
    ];
    $element = $element + $widget;

    $element['#attributes']['class'][] = 'handsontable-widget';
    $element['#attached']['library'] = ['handsontable/widget'];

    return $element;
  }


}
