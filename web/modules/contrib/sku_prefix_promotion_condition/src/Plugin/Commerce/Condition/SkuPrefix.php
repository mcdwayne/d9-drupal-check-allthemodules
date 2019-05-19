<?php

namespace Drupal\sku_prefix_promotion_condition\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the SKU prefix condition for order items.
 *
 * @CommerceCondition(
 *   id = "sku_prefix",
 *   label = @Translation("SKU prefix"),
 *   display_label= @Translation("Sku prefix"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order_item",
 * )
 */
class SkuPrefix extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sku_prefix' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['sku_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SKU prefix'),
      '#description' => $this->t('Enter a SKU prefix to use as a condition for this offer.'),
      '#default_value' => $this->configuration['sku_prefix'],
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
    $this->configuration['sku_prefix'] = $values['sku_prefix'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }
    if (!method_exists($purchased_entity, 'getSku')) {
      // Not sure what to do then.
      return FALSE;
    }
    $sku = $purchased_entity->getSku();
    if (strpos($sku, $this->configuration['sku_prefix']) === 0) {
      return TRUE;
    }
    return FALSE;
  }

}
