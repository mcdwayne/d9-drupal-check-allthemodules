<?php

namespace Drupal\ubercart_funds\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides a block for site balance.
 *
 * @Block(
 *   id = "user_operations",
 *   admin_label = @Translation("User operations")
 * )
 */
class FundsUserOperations extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $account;
  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account, ConfigFactory $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->config = $config->get('uc_funds.settings');
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
      $container->get('config.factory')
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
    $withdrawal_methods = $this->config->get('withdrawal_methods')['methods'];

    return [
      '#theme' => 'user_operations',
      '#withdrawal_methods' => $withdrawal_methods,
    ];
  }

}
