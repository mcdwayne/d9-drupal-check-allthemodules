<?php

namespace Drupal\devel_mode;

use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Drupal\Component\Utility\NestedArray;

/**
 * Add config provider.
 */
class ConfigProvider implements ConfigProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigs($container = NULL) {
    $config = $this->getDefaultConfigs();
    if (!$container) {
      $container = \Drupal::getContainer();
    }
    if ($container->hasParameter('devel_mode.config')) {
      $develModeConfig = $container->getParameter('devel_mode.config');
      $config = NestedArray::mergeDeep($config, $develModeConfig);
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfigs() {
    return [
      'disable_preprocess_js' => TRUE,
      'disable_preprocess_css' => FALSE,
      'modules' => [
        'devel',
      ],
      'twig' => [
        'debug' => TRUE,
        'auto_reload' => TRUE,
        'cache' => FALSE,
      ],
      'cache.bin' => [
        'render',
      ],
    ];
  }

}
