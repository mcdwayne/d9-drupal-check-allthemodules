<?php

namespace Drupal\commerce_funds\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Database\Connection;
use Drupal\commerce_price\Entity\Currency;

/**
 * Provides an admin block for user balances.
 *
 * @Block(
 *   id = "admin_user_balances",
 *   admin_label = @Translation("User balances")
 * )
 */
class FundsAdminUserBalances extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current path.
   *
   * @var path\Drupal\Core\Path\CurrentPathStack
   */
  protected $path;

  /**
   * The db connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
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
    $path_args = explode('/', $this->path->getPath());

    if ($path_args[1] == 'user' && is_numeric($path_args[2])) {
      $account = \Drupal::request()->get('user');
      $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($account);

      foreach ($balance as $currency_code => $amount) {
        $symbol = Currency::load($currency_code)->getSymbol();
        $balance[$currency_code] = $symbol . $amount;
      }

      return [
        '#theme' => 'admin_user_balances',
        '#balance' => $balance ? $balance : 0,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
