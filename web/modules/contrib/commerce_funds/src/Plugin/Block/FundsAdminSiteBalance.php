<?php

namespace Drupal\commerce_funds\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\commerce_price\Entity\Currency;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "admin_site_balance",
 *   admin_label = @Translation("Site balance")
 * )
 */
class FundsAdminSiteBalance extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Defines variables to be used later.
   *
   * @var connection\Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $balance = \Drupal::service('commerce_funds.transaction_manager')->loadSiteBalance();

    foreach ($balance as $currency_code => $amount) {
      $symbol = Currency::load($currency_code)->getSymbol();
      $balance[$currency_code] = $symbol . $amount;
    }

    return [
      '#theme' => 'admin_site_balance',
      '#balance' => $balance ? $balance : 0,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
