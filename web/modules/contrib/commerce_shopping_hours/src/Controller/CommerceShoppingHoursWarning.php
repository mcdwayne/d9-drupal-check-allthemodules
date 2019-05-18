<?php

namespace Drupal\commerce_shopping_hours\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_shopping_hours\CommerceShoppingHoursService;

/**
 * Class CommerceShoppingHoursWarning.
 *
 * @package Drupal\commerce_shopping_hours\Controller
 */
class CommerceShoppingHoursWarning extends ControllerBase {

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
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory, CommerceShoppingHoursService $commerce_shopping_hours_service) {
    $this->configFactory = $config_factory;
    $this->commerceShoppingHoursService = $commerce_shopping_hours_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('commerce_shopping_hours.commerce_shopping_hours_service')
    );
  }

  /**
   * Display 'Shop closed' page.
   */
  public function index() {
    $config = $this->configFactory->get('commerce_shopping_hours.settings');
    $message = $config->get('message');
    $show_shopping_hours = $config->get('show_shopping_hours');
    $shopping_hours = $this->commerceShoppingHoursService->getShoppingHours();

    $message = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t($message) . '</p>',
      '#cache' => ['max-age' => 0],
    ];

    $shopping_hours = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Shopping hours:') . ' ' . $shopping_hours['from'] . ' - ' . $shopping_hours['to'] . '</p>',
    ];

    $output = [];
    $output[] = $message;
    if ($show_shopping_hours) {
      $output[] = $shopping_hours;
    }

    return $output;
  }

}
