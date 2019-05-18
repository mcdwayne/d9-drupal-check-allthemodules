<?php

namespace Drupal\commerce_avatax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\RemoteTaxTypeBase;
use Drupal\Component\Utility\Html;

/**
 * Provides the Avatax remote tax type.
 *
 * @CommerceTaxType(
 *   id = "avatax",
 *   label = "Avatax",
 * )
 */
class Avatax extends RemoteTaxTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_inclusive' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    // We don't inject the config factory on purpose to ease testing config
    // changes.
    $config = \Drupal::configFactory()->get('commerce_avatax.settings');
    return !$config->get('disable_tax_calculation');
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    // We don't inject the Avatax library on purpose, otherwise the
    // library is instantiated too early during the tests causing the config
    // changes to not be taken into account...
    $avatax_lib = \Drupal::service('commerce_avatax.avatax_lib');
    $response_body = $avatax_lib->transactionsCreate($order);

    // Do not go further unless there have been lines added.
    if (empty($response_body['lines'])) {
      return;
    }
    $currency_code = $order->getTotalPrice() ? $order->getTotalPrice()->getCurrencyCode() : $order->getStore()->getDefaultCurrencyCode();
    $adjustments = [];
    $applied_adjustments = [];
    foreach ($response_body['lines'] as $tax_adjustment) {
      $label = isset($tax_adjustment['details'][0]['taxName']) ? Html::escape($tax_adjustment['details'][0]['taxName']) : $this->t('Sales tax');
      $adjustments[$tax_adjustment['lineNumber']] = [
        'amount' => $tax_adjustment['tax'],
        'label' => $label,
      ];
    }

    // Add tax adjustments to order items.
    foreach ($order->getItems() as $item) {
      if (!isset($adjustments[$item->uuid()])) {
        continue;
      }
      $item->addAdjustment(new Adjustment([
        'type' => 'tax',
        'label' => $adjustments[$item->uuid()]['label'],
        'amount' => new Price((string) $adjustments[$item->uuid()]['amount'], $currency_code),
        'source_id' => $this->pluginId . '|' . $this->entityId,
      ]));
      $applied_adjustments[$item->uuid()] = $item->uuid();
    }

    // If we still have Tax adjustments to apply, add a single one to the order.
    $remaining_adjustments = array_diff_key($adjustments, $applied_adjustments);
    if (!$remaining_adjustments) {
      return;
    }
    $tax_adjustment_total = NULL;
    // Calculate the total Tax adjustment to add.
    foreach ($remaining_adjustments as $remaining_adjustment) {
      $adjustment_amount = new Price((string) $remaining_adjustment['amount'], $currency_code);
      $tax_adjustment_total = $tax_adjustment_total ? $tax_adjustment_total->add($adjustment_amount) : $adjustment_amount;
    }
    $order->addAdjustment(new Adjustment([
      'type' => 'tax',
      'label' => $this->t('Sales tax'),
      'amount' => $tax_adjustment_total,
      'source_id' => $this->pluginId . '|' . $this->entityId,
    ]));
  }

}
