<?php

namespace Drupal\ubercart_funds\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;

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
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   * @var \Drupal\Core\Database\Connection
   */
  protected $account;
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
    $connection = $this->connection;

    $query = $connection->query("SELECT balance FROM uc_funds_user_funds WHERE uid = :uid", [
      ':uid' => $this->account->id(),
    ]);
    $balance = $query->fetchObject();

    return [
      '#theme' => 'user_balance',
      '#balance' => $balance ? uc_currency_format($balance->balance / 100) : 0,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
