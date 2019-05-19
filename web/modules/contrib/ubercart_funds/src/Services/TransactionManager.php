<?php

namespace Drupal\ubercart_funds\Services;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\Entity\OrderProduct;
use Drupal\ubercart_funds\Entity\Transaction;
use Drupal\ubercart_funds\Entity\TransactionInterface;

/**
 * Transaction manager class.
 */
class TransactionManager {

  use StringTranslationTrait;

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   * @var \Drupal\Core\Database\Connection
   */
  protected $entityManager;
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, Connection $connection) {
    $this->entityManager = $entity_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Add deposit amount to user balance.
   */
  public function addDepositToBalance(Order $order, OrderProduct $product) {
    $amount = $product->get('price')->getValue()[0]['value'];
    $payment_method = $order->getPaymentMethodId();
    $fee = \Drupal::service('ubercart_funds.fees_manager')->calculateTransactionFee('deposit_' . $payment_method, $amount);
    // Override transaction after fee as it is substracted here.
    $fee['net_amount'] = intval($amount * 100) - $fee['fee'];

    $transaction = Transaction::create([
      'issuer' => $order->getOwnerId(),
      'recipient' => $order->getOwnerId(),
      'type' => 'deposit',
      'method' => $payment_method,
      'brut_amount' => intval($amount * 100),
      'net_amount' => $fee['net_amount'],
      'fee' => $fee['fee'],
      'currency' => $order->getCurrency(),
      'status' => 'Completed',
      'notes' => $this->t('Deposit of @amount (@currency)', [
        '@amount' => $fee['net_amount'] / 100,
        '@currency' => $order->getCurrency(),
      ]),
    ]);
    $transaction->save();

    $this->performTransaction($transaction);
  }

  /**
   * Update balances.
   */
  public function performTransaction(TransactionInterface $transaction) {

    $type = $transaction->bundle();

    if ($type == 'deposit') {
      $this->addFundsToBalance($transaction, $transaction->getIssuer());
      $this->updateSiteBalance($transaction);
    }

    if ($type == 'transfer') {
      $this->addFundsToBalance($transaction, $transaction->getRecipient());
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
      $this->updateSiteBalance($transaction);
    }

    if ($type == 'escrow') {
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
    }

    if ($type == 'withdrawal_request') {
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
      $this->updateSiteBalance($transaction);
    }

    if ($type == 'payment') {
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
      $this->addFundsToBalance($transaction, $transaction->getRecipient());
    }

  }

  /**
   * Add funds from balance.
   */
  public function addFundsToBalance(TransactionInterface $transaction, AccountInterface $account) {
    $brut_amount = $transaction->getBrutAmount();
    // Cover case where it's an escrow cancelled.
    if ($transaction->bundle() == "escrow" && $account->id() == $transaction->getIssuerId()) {
      $brut_amount = $transaction->getNetAmount();
    }

    $balance = $this->loadAccountBalance($account);
    $balance += $brut_amount;

    $this->connection->merge('uc_funds_user_funds')
      ->insertFields([
        'uid' => $account->id(),
        'balance' => $balance,
      ])
      ->updateFields([
        'balance' => $balance,
      ])
      ->key(['uid' => $account->id()])
      ->execute();
  }

  /**
   * Remove Funds from balance.
   */
  public function removeFundsFromBalance(TransactionInterface $transaction, AccountInterface $account) {
    $net_amount = $transaction->getNetAmount();
    $currency_code = $transaction->getCurrencyCode();

    $balance = $this->loadAccountBalance($account);
    $balance -= $net_amount;

    $this->connection->merge('uc_funds_user_funds')
      ->insertFields([
        'uid' => $account->id(),
        'balance' => $balance,
      ])
      ->updateFields([
        'balance' => $balance,
      ])
      ->key(['uid' => $account->id()])
      ->execute();
  }

  /**
   * Update balances.
   */
  public function updateSiteBalance(TransactionInterface $transaction) {
    $site_balance = $this->loadSiteBalance();

    if ($transaction->bundle() !== 'payment') {
      $site_balance += $transaction->getFee();
    }
    if ($transaction->bundle() === 'payment') {
      $site_balance += $transaction->getNetAmount();
    }

    $this->connection->merge('uc_funds_user_funds')
      ->key(['uid' => 1])
      ->updateFields([
        'balance' => $site_balance,
      ])
      ->execute();
  }

  /**
   * Load an account balance.
   *
   * Load balance from a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return array
   *   The user balance.
   */
  public function loadAccountBalance(AccountInterface $account) {
    // Check if issuer balance exists.
    $balance_exist = $this->connection->query("SELECT * FROM uc_funds_user_funds WHERE uid = :uid", [
      ':uid' => $account->id(),
    ])->fetchObject();

    $balance = $balance_exist ? $balance_exist->balance : 0;

    return $balance;
  }

  /**
   * Load global site balance.
   *
   * Load balance from admin user.
   *
   * @return array
   *   The site balance.
   */
  public function loadSiteBalance() {
    // Check if issuer balance exists.
    $balance_exist = $this->connection->query("SELECT * FROM uc_funds_user_funds WHERE uid = :uid", [
      ':uid' => 1,
    ])->fetchObject();

    $balance = $balance_exist ? $balance_exist->balance : 0;

    return $balance;
  }

  /**
   * Get the transaction currency.
   *
   * @param int $transaction_id
   *   The transaction id.
   *
   * @return Drupal\ubercart_price\Entity\Currency
   *   The transaction currency.
   */
  public function getTransactionCurrency($transaction_id) {
    $currency = Transaction::load($transaction_id)->getCurrencyCode();

    return $currency;
  }

}
