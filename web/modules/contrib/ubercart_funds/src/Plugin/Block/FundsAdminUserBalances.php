<?php

namespace Drupal\ubercart_funds\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Database\Connection;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "admin_user_balances",
 *   admin_label = @Translation("User balances")
 * )
 */
class FundsAdminUserBalances extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   * @var \Drupal\Core\Database\Connection
   */
  protected $path;
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $path, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->path = $path;
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
    $container->get('path.current'),
    $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer transactions');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_path = $this->path->getPath();
    $path_args = explode('/', $current_path);

    $connection = $this->connection;

    if ($path_args[1] == 'user' && is_numeric($path_args[2])) {
      $user_uid = $path_args[2];
      $query = $connection->query("SELECT balance FROM uc_funds_user_funds WHERE uid = :uid", [
        ':uid' => $user_uid,
      ]);
      $balance = $query->fetchObject();

      return [
        '#theme' => 'admin_user_balances',
        '#balance' => $balance ? uc_currency_format($balance->balance / 100) : 0,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
