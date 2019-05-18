<?php

namespace Drupal\radioactivity\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'radioactivity_energy' widget.
 *
 * @FieldWidget(
 *   id = "radioactivity_energy",
 *   label = @Translation("Energy"),
 *   field_types = {
 *     "radioactivity"
 *   }
 * )
 */
class RadioactivityEnergy extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'energy' => 0.0,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = [
      '#type' => 'textfield',
      '#pattern' => '[0-9]+(\.[0-9]+)?',
      '#default_value' => isset($items[$delta]->energy) ? $items[$delta]->energy : 0,
    ] + $element;

    if (!isset($form['advanced'])) {
      return ['energy' => $element];
    }

    // Put the form element into the form's "advanced" group.
    return [
      '#type' => 'details',
      '#group' => 'advanced',
      '#title' => $element['#title'],
      '#required' => TRUE,
      '#weight' => '40',
      'energy' => $element,
    ];
  }

}
