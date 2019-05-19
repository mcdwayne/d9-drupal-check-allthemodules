<?php

namespace Drupal\webpack_vuejs\Plugin\Webpack\ConfigProcessors;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webpack\Plugin\Webpack\ConfigProcessors\ConfigProcessorBase;
use Drupal\webpack\Plugin\Webpack\ConfigProcessors\WebpackConfigNodeModulesNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Vue.js loader to webpack.
 *
 * @WebpackConfigProcessor(
 *   id = "vuejs",
 *   weight = -1,
 * )
 */
class VueJs extends ConfigProcessorBase implements ContainerFactoryPluginInterface {

  // TODO: Move the resolve alias option to config.

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * VueJs constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('logger.channel.webpack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processConfig(&$config, $context) {
    try {
      $absoluteNodeModulesPath = $this->getPathToNodeModules();
      $config['#lines_before'][] = "const VueLoaderPlugin = require('$absoluteNodeModulesPath/vue-loader/lib/plugin');";
      $config['module']['rules'][] = [
        'test' => '`/.vue$/`',
        'loader' => 'vue-loader',
      ];
      $config['resolve']['alias']['vue$'] = 'vue/dist/vue.esm.js';
      $config['plugins'][] = "`new VueLoaderPlugin()`";
    } catch (WebpackConfigNodeModulesNotFoundException $e) {
      $this->logger->error('Node modules folder not found in the webroot ');
    }
  }

}
