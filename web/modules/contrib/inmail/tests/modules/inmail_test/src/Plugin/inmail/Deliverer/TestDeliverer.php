<?php

namespace Drupal\inmail_test\Plugin\inmail\Deliverer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inmail\Plugin\inmail\Deliverer\DelivererBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delivers a dummy message and counts invocations.
 *
 * @Deliverer(
 *   id = "test_deliverer",
 *   label = @Translation("Test Deliverer")
 * )
 */
class TestDeliverer extends DelivererBase implements ContainerFactoryPluginInterface {

  use TestDelivererTrait;

  /**
   * Constructs a TestDeliverer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function success($key) {
    parent::success($key);

    $this->setSuccess($key);
  }

}
