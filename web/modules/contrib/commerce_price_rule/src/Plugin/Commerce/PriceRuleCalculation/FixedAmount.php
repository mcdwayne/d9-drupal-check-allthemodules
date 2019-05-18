<?php

namespace Drupal\commerce_price_rule\Plugin\Commerce\PriceRuleCalculation;

use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce_price_rule\Entity\PriceRuleInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Calculation that sets the product price to a fixed amount.
 *
 * @CommercePriceRuleCalculation(
 *   id = "fixed_amount",
 *   label = @Translation("Fixed amount"),
 *   entity_type = "commerce_product_variation",
 * )
 */
class FixedAmount extends PriceRuleCalculationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'amount' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);

    $amount = $this->configuration['amount'];
    // A bug in the plugin_select form element causes $amount to be incomplete.
    if (isset($amount) && !isset($amount['number'], $amount['currency_code'])) {
      $amount = NULL;
    }

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Amount'),
      '#default_value' => $amount,
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
    $this->configuration['amount'] = $values['amount'];
  }

  /**
   * Gets the calculation amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The amount, or NULL if unknown.
   */
  protected function getAmount() {
    if (empty($this->configuration['amount'])) {
      return;
    }

    $amount = $this->configuration['amount'];
    return new Price($amount['number'], $amount['currency_code']);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t(
      '@amount',
      ['@amount' => $this->getAmount()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculate(
    EntityInterface $entity,
    PriceRuleInterface $price_rule,
    $quantity,
    Context $context
  ) {
    $this->assertEntity($entity);
    return $this->getAmount();
  }

}
