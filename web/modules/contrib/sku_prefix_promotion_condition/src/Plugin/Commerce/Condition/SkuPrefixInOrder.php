<?php

namespace Drupal\sku_prefix_promotion_condition\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the SKU prefix in order condition.
 *
 * @CommerceCondition(
 *   id = "sku_prefixes_on_order",
 *   label = @Translation("SKU prefixes"),
 *   display_label= @Translation("Sku prefixes"),
 *   category = @Translation("Order"),
 *   entity_type = "commerce_order",
 * )
 */
class SkuPrefixInOrder extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sku_prefixes' => '',
      'exclude' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['sku_prefixes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SKU prefixes'),
      '#description' => $this->t('Enter some SKU prefixes to use as a condition for this offer. One per line'),
      '#default_value' => $this->configuration['sku_prefixes'],
      '#required' => TRUE,
    ];
    $form['exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclusive condition'),
      '#description' => $this->t('Check this box if the condition should be exclusive. IE the condition will pass if the order contains none of the SKUs listed.'),
      '#default_value' => $this->configuration['exclude'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['sku_prefixes'] = $values['sku_prefixes'];
    $this->configuration['exclude'] = $values['exclude'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $exclude = (bool) $this->configuration['exclude'];
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $items = $order->getItems();
    $prefixes = preg_split('/\r\n|\r|\n/', $this->configuration['sku_prefixes']);
    foreach ($items as $item) {
      if (!$purchased_entity = $item->getPurchasedEntity()) {
        continue;
      }
      if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_product_variation') {
        continue;
      }
      if (!method_exists($purchased_entity, 'getSku')) {
        // Not sure what to do then.
        continue;
      }
      $sku = $purchased_entity->getSku();
      foreach ($prefixes as $prefix) {
        if (strpos($sku, $prefix) === 0) {
          return !$exclude;
        }
      }
    }
    return $exclude;
  }

}
