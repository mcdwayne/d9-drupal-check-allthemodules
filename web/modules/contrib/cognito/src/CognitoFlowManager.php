<?php

namespace Drupal\cognito;

use Drupal\cognito\Annotation\CognitoFlow;
use Drupal\cognito\Plugin\cognito\CognitoFlowInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The plugin manager for different cognito flows.
 */
class CognitoFlowManager extends DefaultPluginManager implements CognitoFlowManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/cognito/CognitoFlow', $namespaces, $module_handler, CognitoFlowInterface::class, CognitoFlow::class);
    $this->setCacheBackend($cache_backend, 'cognito_flow');
    $this->alterInfo('cognito_flow');
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedFlow() {
    return $this->createInstance('cognitoflow_email');
  }

}
