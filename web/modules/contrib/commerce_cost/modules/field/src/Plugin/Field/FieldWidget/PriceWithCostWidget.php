<?php

namespace Drupal\commerce_cost_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_price_with_cost' widget.
 *
 * @FieldWidget(
 *   id = "commerce_price_with_cost",
 *   label = @Translation("Price with Cost"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class PriceWithCostWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'price_field' => '',
      'cost_field' => '',
      'calculation_type' => 'markup',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $options = [];
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    if (empty($bundle)) {
      return $options;
    }

    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($fields as $field) {
      if ($field->getType() === 'commerce_price') {
        $options[$field->getName()] = $field->getLabel() . ' (' . $field->getName() . ')';
      }
    }

    $form['price_field'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Select Commerce price field'),
      '#default_value' => $options,
    ];
    $form['cost_field'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Select Cost price field'),
      '#default_value' => $this->getSetting('cost_field'),
    ];
    $form['calculation_type'] = [
      '#type' => 'select',
      '#options' => [
        'markup' => $this->t('Markup'),
        'margin' => $this->t('Margin'),
      ],
      '#title' => $this->t('Calculation type'),
      '#default_value' => $this->getSetting('calculation_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'commerce_price';
    $element['#available_currencies'] = array_filter($this->getFieldSetting('available_currencies'));

    // Attach the library.
    $form['#attached']['library'][] = 'commerce_cost_field/sale_price';
    $form['#attached']['drupalSettings']['commerce_cost_field'] = [
      'price_field' => str_replace('_', '-', $this->settings['price_field']),
      'cost_field' => str_replace('_', '-', $this->settings['cost_field']),
      'calculation_type' => $this->settings['calculation_type'],
    ];

    if (!$items[$delta]->isEmpty()) {
      $element['#default_value'] = $items[$delta]->toPrice()->toArray();
    }

    return $element;
  }

}
