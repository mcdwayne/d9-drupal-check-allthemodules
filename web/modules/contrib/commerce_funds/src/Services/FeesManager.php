<?php

namespace Drupal\commerce_funds\Services;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_payment\PaymentOptionsBuilderInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_funds\FundsDefaultCurrency;

/**
 * Fees Manager class.
 */
class FeesManager {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The payment option builder service.
   *
   * @var \Drupal\commerce_payment\PaymentOptionsBuilderInterface
   */
  protected $paymentOptionsBuilder;

  /**
   * The product manager service.
   *
   * @var \Drupal\commerce_funds\Services\ProductManager
   */
  protected $productManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, PaymentOptionsBuilderInterface $payment_options_builder, ProductManager $product_manager) {
    $this->config = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->paymentOptionsBuilder = $payment_options_builder;
    $this->productManager = $product_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('commerce_payment.options_builder'),
      $container->get('commerce_funds.product_manager')
    );
  }

  /**
   * Calculate the fee apply to a deposit.
   *
   * @param Drupal\commerce_order\Entity\Order $order
   *   The order object.
   *
   * @return int
   *   Fee applied to the deposit.
   */
  public function calculateOrderFee(Order $order) {
    $fees = $this->config->get('commerce_funds.settings')->get('fees');
    // No need to go further if no fees are set.
    if (!isset($fees) || !$fees) {
      return 0;
    }
    $payment_method_selected = $order->get('payment_method')->getValue();
    $options = $this->paymentOptionsBuilder->buildOptions($order);
    $default_payment_gateway = $this->paymentOptionsBuilder->selectDefaultOption($order, $options)->getPaymentGatewayId();

    $payment_method = $payment_method_selected ?: $default_payment_gateway;
    $fee_rate = array_key_exists('deposit_' . $payment_method . '_rate', $fees) ? $fees['deposit_' . $payment_method . '_rate'] : 0;
    $fee_fixed = array_key_exists('deposit_' . $payment_method . '_fixed', $fees) ? $fees['deposit_' . $payment_method . '_fixed'] : 0;

    $deposit_amount = $order->getItems()[0]->getTotalPrice()->getNumber();

    $deposit_amount_after_fee_rate = $deposit_amount * (1 + $fee_rate / 100);
    $deposit_amount_after_fee_fixed = $deposit_amount + $fee_fixed;
    $deposit_amount_after_fees = max([$deposit_amount_after_fee_rate, $deposit_amount_after_fee_fixed]);

    $fee = intval(abs($deposit_amount_after_fees - $deposit_amount));

    return $fee;
  }

  /**
   * Apply fees to the order.
   *
   * Create a fee order item and add it to the order.
   *
   * @param Drupal\commerce_order\Entity\Order $order
   *   The order object.
   *
   * @return Drupal\commerce_order\Entity\Order
   *   The order with the fees applied to it.
   */
  public function applyFeeToOrder(Order $order) {
    // Calculate the fee and kill the function if no fee.
    $fee = $this->calculateOrderFee($order);
    if (!$fee) {
      return $order;
    }

    $currency_code = $order->getItems()[0]->getTotalPrice()->getCurrencyCode();
    $product_variation = $this->productManager->createProduct('fee', $fee, $currency_code);
    $updated_order = $this->productManager->updateOrder($order, $product_variation);

    return $updated_order;
  }

  /**
   * Details fees applied to a payment gateway.
   *
   * @param string $payment_gateway
   *   The payment gateway id.
   * @param string $currency_code
   *   The currency_code.
   * @param string $type
   *   The transaction type.
   *
   * @return string
   *   Description of fees applied for the payment gateway.
   */
  public function printPaymentGatewayFees($payment_gateway, $currency_code, $type) {
    $fees = $this->config->get('commerce_funds.settings')->get('fees');
    if (!$fees) {
      return '';
    }

    // Return the numeric key of the fee if found in the array.
    $rate_fee = array_search($type . '_' . $payment_gateway . '_rate', array_keys($fees), TRUE);
    $fixed_fee = array_search($type . '_' . $payment_gateway . '_fixed', array_keys($fees), TRUE);

    // Swap our associative array to a numeric indexed one.
    $fees_keys = array_keys($fees);
    $fees_applied = '';
    if (!$fees[$fees_keys[$rate_fee]] && !$fees[$fees_keys[$fixed_fee]]) {
      $fees_applied = $this->t('(No fee)');
    }
    elseif ($rate_fee && !$fixed_fee) {
      $fees_applied = $this->t('(Fee = @rate_fee%)', [
        '@rate_fee' => $fees[$fees_keys[$rate_fee]],
      ]);
    }
    elseif ($rate_fee && $fixed_fee) {
      $fees_applied = $this->t('(Fees = @rate_fee% min @fixed_fee @currency)', [
        '@rate_fee' => $fees[$fees_keys[$rate_fee]],
        '@fixed_fee' => $fees[$fees_keys[$fixed_fee]],
        '@currency' => $currency_code,
      ]);
    }
    elseif (!$rate_fee && $fixed_fee) {
      $fees_applied = $this->t('(Fee = @fixed_fee @currency)', [
        '@fixed_fee' => $fees[$fees_keys[$fixed_fee]],
        '@currency' => $currency_code,
      ]);
    }

    return $fees_applied;
  }

  /**
   * Calculate the fee apply to a transaction.
   *
   * @param int $brut_amount
   *   The transaction amount.
   * @param string $currency_code
   *   The transaction currency_code.
   * @param string $type
   *   The transaction type.
   *
   * @return array
   *   Fee applied to the transaction.
   */
  public function calculateTransactionFee($brut_amount, $currency_code, $type) {
    $fees = $this->config->get('commerce_funds.settings')->get('fees');
    // No need to go further if no fees are set.
    if (!isset($fees) || !$fees) {
      return [
        'net_amount' => $brut_amount,
        'fee' => 0,
      ];
    }
    $fee_rate = array_key_exists($type . '_rate', $fees) ? $fees[$type . '_rate'] : 0;
    $fee_fixed = array_key_exists($type . '_fixed', $fees) ? $fees[$type . '_fixed'] : 0;

    if ($type == 'payment') {
      $transaction_amount_after_fee_rate = $brut_amount * (1 - $fee_rate / 100);
      $transaction_amount_after_fee_fixed = $brut_amount - $fee_fixed;
      $transaction_amount_after_fees = min([$transaction_amount_after_fee_rate, $transaction_amount_after_fee_fixed]);
    }
    else {
      $transaction_amount_after_fee_rate = $brut_amount * (1 + $fee_rate / 100);
      $transaction_amount_after_fee_fixed = $brut_amount + $fee_fixed;
      $transaction_amount_after_fees = max([$transaction_amount_after_fee_rate, $transaction_amount_after_fee_fixed]);
    }

    $fee = intval(abs($transaction_amount_after_fees - $brut_amount));

    $fee_applied = [
      'net_amount' => $transaction_amount_after_fees,
      'fee' => $fee,
    ];

    return $fee_applied;
  }

  /**
   * Display the fees applied for a transaction type.
   *
   * @param string $transaction_type
   *   Machine name of the transaction type.
   *
   * @return string
   *   Description of fees applied for the transaction.
   */
  public function printTransactionFees($transaction_type) {
    $fees = $this->config->get('commerce_funds.settings')->get('fees') ?: [];
    $store = $this->entityTypeManager->getStorage('commerce_store')->loadDefault();
    $funds_default_currency = new FundsDefaultCurrency($store);
    $currency = $funds_default_currency->printTransactionCurrency();

    $rate_fee = in_array($transaction_type . '_rate', array_keys($fees)) ? $fees[$transaction_type . '_rate'] : 0;
    $fixed_fee = in_array($transaction_type . '_fixed', array_keys($fees)) ? $fees[$transaction_type . '_fixed'] : 0;

    if ($rate_fee && !$fixed_fee) {
      $fees_description = t('An extra commission of @rate_fee% will be applied to your @transaction_type.', [
        '@rate_fee' => $rate_fee,
        '@transaction_type' => $transaction_type,
      ]);
    }
    elseif ($rate_fee && $fixed_fee) {
      $fees_description = t('An extra commission of @rate_fee% with a minimum of @fixed_fee @currency will be applied to your @transaction_type.', [
        '@rate_fee' => $rate_fee,
        '@fixed_fee' => $fixed_fee,
        '@transaction_type' => $transaction_type,
        '@currency' => $currency,
      ]);
    }
    elseif (!$rate_fee && $fixed_fee) {
      $fees_description = t('An extra commission of @fixed_fee @currency will be applied to your @transaction_type.', [
        '@fixed_fee' => $fixed_fee,
        '@transaction_type' => $transaction_type,
        '@currency' => $currency,
      ]);
    }
    else {
      $fees_description = t('Please enter the amount you want to @transaction_type.', [
        '@transaction_type' => $transaction_type,
      ]);
    }

    return $fees_description;
  }

  /**
   * Convert a currency into another.
   *
   * @param int $amount
   *   The transaction amount.
   * @param string $currency_left
   *   The currency to be converted from.
   * @param string $currency_right
   *   The currency to convert into.
   *
   * @return array
   *   New amount and fee applied.
   */
  public function convertCurrencyAmount($amount, $currency_left, $currency_right) {
    $exchange_rates = $this->config->get('commerce_funds.settings')->get('exchange_rates');
    $rate = $exchange_rates[$currency_left . '_' . $currency_right];

    $new_amount = Calculator::multiply($amount, $rate);
    $fee = Calculator::subtract($new_amount, $amount, 2);
    $conversion = [
      'new_amount' => $new_amount,
      'rate' => $rate,
    ];

    return $conversion;
  }

  /**
   * Display the converted amount.
   *
   * @param string $amount
   *   The transaction amount.
   * @param string $currency_left
   *   The currency to be converted from.
   * @param string $currency_right
   *   The currency to convert into.
   *
   * @return string
   *   New amount value after convertion.
   */
  public function printConvertedAmount(string $amount, string $currency_left, string $currency_right) {
    if (!$amount || !$currency_left || !$currency_right) {
      return '0';
    }

    $exchange_rates = $this->config->get('commerce_funds.settings')->get('exchange_rates');
    $rate = $exchange_rates[$currency_left . '_' . $currency_right];
    $symbol = Currency::load($currency_right)->getSymbol();

    return $symbol . Calculator::multiply($amount, $rate);
  }

}
