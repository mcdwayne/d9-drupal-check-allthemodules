<?php

namespace Drupal\business_rules\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BusinessRulesDebugBlock.
 *
 * @package Drupal\business_rules\Plugin\Block
 *
 * @Block(
 *   id = "business_rules_debug_block",
 *   admin_label = @Translation("Business rules debug"),
 *   definition={}
 *
 * )
 */
class BusinessRulesDebugBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Business Rules configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The keyvalue expirable.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  private $keyvalue;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config   = $container->get('config.factory')
      ->get('business_rules.settings');
    $this->keyvalue = $container->get('keyvalue.expirable')
      ->get('business_rules.debug');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    if ($this->config->get('debug_screen')) {
      $session_id = session_id();
      $debug      = $this->keyvalue->get($session_id);
      $this->keyvalue->set($session_id, NULL);

      if ($debug && count($debug)) {
        $output['#attached']['library'][] = 'business_rules/style';
        $output['#attached']['library'][] = 'dbug/dbug';

        $output['business_rules_debug'] = [
          '#type'        => 'details',
          '#title'       => 'Business Rules Debug',
          '#collapsed'   => TRUE,
          '#collapsable' => TRUE,
        ];

        $output['business_rules_debug']['debug'] = $debug;
      }
    }

    return $output;
  }

  /**
   * This block cannot be cacheable.
   *
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer site configuration');
  }

}
