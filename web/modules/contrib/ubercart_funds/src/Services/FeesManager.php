<?php

namespace Drupal\ubercart_funds\Services;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fees Manager class.
 */
class FeesManager {

  use StringTranslationTrait;

  /**
   * Defines default currency code.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   */
  protected $config;
  protected $entityManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager) {
    $this->config = $config_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Calculate the fee applied to a transaction.
   *
   * @param string $type
   *   The transaction type.
   * @param float $brut_amount
   *   The transaction amount.
   *
   * @return array
   *   An associative array with fee * 100
   *   and transaction_after_fee * 100
   *   to be sure values are integer in the db.
   */
  public function calculateTransactionFee($type, $brut_amount) {
    $fees = $this->config->get('uc_funds.settings')->get('fees');
    // No need to go further if no fees are set.
    if (!isset($fees) || !$fees) {
      return [
        'net_amount' => $brut_amount,
        'fee' => 0,
      ];
    }

    $fee_rate = array_key_exists($type . '_rate', $fees) ? intval($fees[$type . '_rate'] * 100) : 0;
    $fee_fixed = array_key_exists($type . '_fixed', $fees) ? intval($fees[$type . '_fixed'] * 100) : 0;

    $brut_amount = intval($brut_amount * 100);
    $amount_after_fee_rate = $brut_amount * (1 + $fee_rate / 10000);
    $amount_after_fee_fixed = $brut_amount + $fee_fixed;
    $amount_after_fee = max([$amount_after_fee_rate, $amount_after_fee_fixed]);

    $fee = intval(abs($amount_after_fee - $brut_amount));

    $result = [
      'net_amount' => $amount_after_fee,
      'fee' => $fee,
    ];

    return $result;
  }

  /**
   * Calculate the fee apply for a payment method.
   *
   * @param string $payment_method
   *   Plugin id for the payment method.
   *
   * @return string
   *   Description of fees applied for the payment method.
   */
  public function printMethodFee($payment_method) {
    $fees_applied = NULL;
    $fees = \Drupal::config('uc_funds.settings')->get('fees') ?: [];
    $currency = \Drupal::config('uc_store.settings')->get('currency.code');

    // Return the numeric key of the fee if found in the array.
    $rate_fee = array_search('deposit_' . $payment_method . '_rate', array_keys($fees), TRUE);
    $fixed_fee = array_search('deposit_' . $payment_method . '_fixed', array_keys($fees), TRUE);
    // Swap our associative array to a numeric indexed one.
    $fees_keys = array_keys($fees);

    if ($rate_fee && !$fixed_fee) {
      $fees_applied = t('(Fee = @rate_fee%)', [
        '@rate_fee' => $fees[$fees_keys[$rate_fee]],
      ]);
    }
    elseif ($rate_fee && $fixed_fee) {
      $fees_applied = t('(Deposit fees = @rate_fee% min @fixed_fee @currency)', [
        '@rate_fee' => $fees[$fees_keys[$rate_fee]],
        '@fixed_fee' => $fees[$fees_keys[$fixed_fee]],
        '@currency' => $currency,
      ]);
    }
    elseif (!$rate_fee && $fixed_fee) {
      $fees_applied = t('(Deposit fees = @fixed_fee @currency)', [
        '@fixed_fee' => $fees[$fees_keys[$fixed_fee]],
        '@currency' => $currency,
      ]);
    }

    return $fees_applied;
  }

  /**
   * Display the fees applied for a transaction type.
   *
   * @param string $type
   *   Machine name of the transaction type.
   *
   * @return string
   *   Description of fees applied for the transaction.
   */
  public function printTransactionFees($type) {
    $fees = \Drupal::config('uc_funds.settings')->get('fees');
    $currency = \Drupal::config('uc_store.settings')->get('currency.code');

    // Return the numeric key of the fee if found in the array.
    $rate_fee = in_array($type . '_rate', array_keys($fees)) ? $fees[$type . '_rate'] : FALSE;
    $fixed_fee = in_array($type . '_fixed', array_keys($fees)) ? $fees[$type . '_fixed'] : FALSE;

    if ($rate_fee && !$fixed_fee) {
      $fees_description = t('An extra commission of @rate_fee% will be applied to your @transaction_type.', [
        '@rate_fee' => $fees[$type . '_rate'],
        '@transaction_type' => $type,
      ]);
    }
    elseif ($rate_fee && $fixed_fee) {
      $fees_description = t('An extra commission of @rate_fee% with a minimum of @fixed_fee @currency will be applied to your @transaction_type.', [
        '@rate_fee' => $fees[$type . '_rate'],
        '@fixed_fee' => $fees[$type . '_fixed'],
        '@transaction_type' => $type,
        '@currency' => $currency,
      ]);
    }
    elseif (!$rate_fee && $fixed_fee) {
      $fees_description = t('An extra commission of @fixed_fee @currency will be applied to your @transaction_type.', [
        '@fixed_fee' => $fees[$type . '_fixed'],
        '@transaction_type' => $type,
        '@currency' => $currency,
      ]);
    }
    else {
      $fees_description = t('Please enter an amount in @currency you want to @transaction_type.', [
        '@transaction_type' => $type,
        '@currency' => $currency,
      ]);
    }

    return $fees_description;
  }

}
