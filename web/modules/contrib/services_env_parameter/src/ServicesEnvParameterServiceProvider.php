<?php

namespace Drupal\services_env_parameter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Implements a service provider.
 */
class ServicesEnvParameterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    foreach ($_ENV as $key => $value) {
      if (strpos($key, 'DRUPAL_SERVICE_') === 0) {
        // Remove DRUPAL_SERVICE_ prefix.
        $key = substr($key, strlen('DRUPAL_SERVICE_'));
        $key = str_replace('__', '.', $key);
        // Split by '____' which is '._' now.
        $parts = explode('._', $key);
        if ($container->getParameterBag()->has($parts[0])) {
          $key = $parts[0];
          unset($parts[0]);
          $this->applyParamterValue($container, $key, $parts, $value);
        }
      }
    }
  }

  /**
   * Internal helper for applying parameter values.
   */
  private function applyParamterValue(ContainerBuilder $container, $key, $sub_keys, $value) {
    if (!$sub_keys) {
      $container->setParameter($key, $value);
    }
    else {
      // Take care of setting nested parameters correctly.
      $parameter = $container->getParameter($key);
      NestedArray::setValue($parameter, $sub_keys, $value);
      $container->setParameter($key, $parameter);
    }
  }

}
