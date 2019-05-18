<?php

namespace Drupal\adva\ParamConverter;

use Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface;
use Drupal\adva\Plugin\adva\OverridingAccessConsumerInterface;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a ParamConverter for Advanced Access Plugins.
 */
class AdvancedAccessParamConverter implements ParamConverterInterface {

  /**
   * Current access consumer manager.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface
   */
  public $accessConsumerManager;

  /**
   * Creates a new AdvancedAccessParamConverter.
   *
   * @param \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface $access_consumer_manager
   *   The current access consumer manager instance.
   */
  public function __construct(AccessConsumerManagerInterface $access_consumer_manager) {
    $this->accessConsumerManager = $access_consumer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $consumer = $this->accessConsumerManager->getConsumer($value);
    if ($consumer && $consumer instanceof OverridingAccessConsumerInterface) {
      return $consumer;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'access_consumer');
  }

}
