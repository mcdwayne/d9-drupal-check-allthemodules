<?php

namespace Drupal\commerce_add_to_cart_matrix\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_add_to_cart_matrix\Form\AddToCartMatrix;

/**
 * Plugin implementation of the 'commerce_add_to_cart_matrix' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_add_to_cart_matrix",
 *   label = @Translation("Add to cart matrix"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class AddToCartMatrixFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'combine' => TRUE,
      'vertical_attribute' => '',
      'horizontal_attribute' => '',
      'reverse_vertical_order' => FALSE,
      'reverse_horizontal_order' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => t('Combine order items containing the same product variation.'),
      '#description' => t('The order item type, referenced product variation, and data from fields exposed on the Add to Cart form must all match to combine.'),
      '#default_value' => $this->getSetting('combine'),
    ];

    $form['vertical_attribute'] = [
      '#type' => 'select',
      '#title' => t('Vertical attribute'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('vertical_attribute'),
      '#options' => $this->getAvailableAttributes($form_state),
    ];

    $form['reverse_vertical_order'] = [
      '#type' => 'checkbox',
      '#title' => t('Reverse vertical attribute'),
      '#default_value' => $this->getSetting('reverse_vertical_order'),
    ];

    $form['horizontal_attribute'] = [
      '#type' => 'select',
      '#title' => t('Horizontal attribute'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('horizontal_attribute'),
      '#options' => $this->getAvailableAttributes($form_state),
    ];

    $form['reverse_horizontal_order'] = [
      '#type' => 'checkbox',
      '#title' => t('Reverse horizontal attribute'),
      '#default_value' => $this->getSetting('reverse_horizontal_order'),
    ];

    return $form;
  }

  /**
   * Gets the available attributes.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   *
   * @return array
   *   The available options.
   */
  private function getAvailableAttributes(FormStateInterface $formState) {
    /** @var \Drupal\field_ui\Form\EntityViewDisplayEditForm $callbackObject */
    $callbackObject = $formState->getBuildInfo()['callback_object'];
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $type */
    $type = $callbackObject->getEntity();

    /** @var \Drupal\field\Entity\FieldConfig $variationFieldDefinition */
    $variationFieldDefinition = $type->get('fieldDefinitions')['variations'];
    $handler_settings = $variationFieldDefinition->getSetting('handler_settings');

    /** @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface $variationFieldManager */
    $variationFieldManager = \Drupal::getContainer()
      ->get('commerce_product.attribute_field_manager');

    $options = [];

    foreach ($handler_settings['target_bundles'] as $variation_bundle) {
      $available_attributes = $variationFieldManager->getFieldMap($variation_bundle);
      foreach ($available_attributes as $attribute_info) {
        $options[$attribute_info['field_name']] = $attribute_info['attribute_id'];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('combine')) {
      $summary[] = $this->t('Combine order items containing the same product variation.');
    }
    else {
      $summary[] = $this->t('Do not combine order items containing the same product variation.');
    }

    if ($this->getSetting('vertical_attribute')) {
      $summary[] = $this->t('Vertical attribute is: %attribute.', ['%attribute' => $this->getSetting('vertical_attribute')]);
    }
    else {
      $summary[] = $this->t('No vertical attribute selected.');
    }

    if ($this->getSetting('horizontal_attribute')) {
      $summary[] = $this->t('Horizontal attribute is: %attribute.', ['%attribute' => $this->getSetting('horizontal_attribute')]);
    }
    else {
      $summary[] = $this->t('No horizontal attribute selected.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $form = AddToCartMatrix::create(\Drupal::getContainer());
    $form->setEntity($items->getParent()->getValue());
    $form->setField($this);
    return \Drupal::formBuilder()->getForm($form);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $has_cart = \Drupal::moduleHandler()->moduleExists('commerce_cart');
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $has_cart && $entity_type === 'commerce_product' && $field_name === 'variations';
  }

}
