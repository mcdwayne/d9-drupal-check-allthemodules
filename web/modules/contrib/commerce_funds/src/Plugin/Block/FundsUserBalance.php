<?php

namespace Drupal\commerce_funds\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\commerce_price\Entity\Currency;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "user_balance",
 *   admin_label = @Translation("User balance")
 * )
 */
class FundsUserBalance extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The db connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'deposit funds');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($this->account);

    foreach ($balance as $currency_code => $amount) {
      $symbol = Currency::load($currency_code)->getSymbol();
      $balance[$currency_code] = $symbol . $amount;
    }

    return [
      '#theme' => 'user_balance',
      '#balance' => $balance ? $balance : 0,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
