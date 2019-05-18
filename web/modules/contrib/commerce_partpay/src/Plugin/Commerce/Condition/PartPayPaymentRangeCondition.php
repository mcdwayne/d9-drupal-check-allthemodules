<?php

namespace Drupal\commerce_partpay\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the total price condition for orders.
 *
 * @CommerceCondition(
 *   id = "partpay_payment_range_condition",
 *   label = @Translation("PartPay payment range condition"),
 *   display_label = @Translation("Payment range"),
 *   category = @Translation("PartPay"),
 *   entity_type = "commerce_order",
 * )
 */
class PartPayPaymentRangeCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    $key = 'minimumAmount';
    $default = !empty($this->configuration[$key]) ? $this->configuration[$key] : 50;
    $form[$key] = [
      '#type' => 'commerce_price',
      '#title' => t('Minimum amount'),
      '#default_value' => ['number' => $default, 'currency_code' => 'NZD'],
      '#required' => TRUE,
    ];

    $key = 'maximumAmount';
    $default = !empty($this->configuration[$key]) ? $this->configuration[$key] : 1000;
    $form[$key] = [
      '#type' => 'commerce_price',
      '#title' => t('Maximum amount'),
      '#default_value' => ['number' => $default, 'currency_code' => 'NZD'],
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
    $this->configuration['minimumAmount'] = $values['minimumAmount']['number'];
    $this->configuration['maximumAmount'] = $values['maximumAmount']['number'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;

    if (
      $order->getTotalPrice()->getNumber() >= $this->configuration['minimumAmount']
      && $order->getTotalPrice()->getNumber() <= $this->configuration['maximumAmount']
    ) {
      return TRUE;
    }

    return FALSE;
  }

}
