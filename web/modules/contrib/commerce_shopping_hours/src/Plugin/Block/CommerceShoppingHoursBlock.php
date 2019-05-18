<?php

namespace Drupal\commerce_shopping_hours\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_shopping_hours\CommerceShoppingHoursService;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides a 'CommerceShoppingHoursBlock' block.
 *
 * @Block(
 *  id = "commerce_shopping_hours_block",
 *  admin_label = @Translation("Commerce Shopping Hours"),
 *  category = @Translation("Commerce")
 * )
 */
class CommerceShoppingHoursBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\commerce_shopping_hours\CommerceShoppingHoursService definition.
   *
   * @var Drupal\commerce_shopping_hours\CommerceShoppingHoursService
   */
  protected $commerceShoppingHoursService;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param Drupal\commerce_shopping_hours\CommerceShoppingHoursService $commerce_shopping_hours_service
   *   The commerce shopping hours service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $config_factory,
    CommerceShoppingHoursService $commerce_shopping_hours_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->commerceShoppingHoursService = $commerce_shopping_hours_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('commerce_shopping_hours.commerce_shopping_hours_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('commerce_shopping_hours.settings');
    $is_shop_open = $this->commerceShoppingHoursService->isShopOpen();
    $message = $config->get('message');
    $show_shopping_hours = $config->get('show_shopping_hours');
    $shopping_hours = $this->commerceShoppingHoursService->getShoppingHours();

    return [
      '#theme' => 'commerce_shopping_hours',
      '#is_open' => $is_shop_open,
      '#message' => $this->t($message),
      '#show_shopping_hours' => $show_shopping_hours,
      '#shopping_hours' => $shopping_hours,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' =>
          ['commerce_shopping_hours/commerce_shopping_hours'],
      ],
    ];
  }

}
