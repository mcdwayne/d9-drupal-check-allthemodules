<?php

namespace Drupal\commerce_rental\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the rentable product variation types condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_rentable_variation_type",
 *   label = @Translation("Rentable product variation type"),
 *   display_label = @Translation("Limit by rentable product variation types"),
 *   category = @Translation("Rental"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemRentableVariationType extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'variation_types' => [],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $variation_types = ProductVariationType::loadMultiple();
    $options = [];
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    foreach ($variation_types as $variation_type) {
      if ($variation_type->hasTrait('purchasable_entity_rentable')) {
        $options[$variation_type->id()] = $variation_type->id();
      }
    }
    $form['variation_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Rentable product variation types'),
      '#default_value' => $this->configuration['variation_types'],
      '#options' => $options,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['variation_types'] = $values['variation_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
    $purchasable_entity = $order_item->getPurchasedEntity();
    $variation_type = ProductVariationType::load($purchasable_entity->bundle());
    if (!$purchasable_entity ||!$variation_type || !$variation_type->hasTrait('purchasable_entity_rentable')) {
      return FALSE;
    }
    return in_array($purchasable_entity->bundle(), $this->configuration['variation_types']);
  }

}
