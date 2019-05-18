<?php

namespace Drupal\devel_mode;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds cache_bins parameter to the container.
 */
class DevelMode implements CompilerPassInterface {

  /**
   * Implements CompilerPassInterface::process().
   *
   * Collects the cache bins into the cache_bins parameter.
   */
  public function process(ContainerBuilder $container) {
    $config = $container->getDefinition('devel_mode.config_provider');
    $config = $container->createService($config, 'devel_mode.config_provider');
    $configs = $config->getConfigs($container);
    $defaults = $container->getParameter('cache_default_bin_backends');

    $container->setParameter('twig.config', $twig);

    $renderer = $container->getParameter('renderer.config');
    $container->setParameter('http.response.debug_cacheability_headers', TRUE);

    $container->setParameter('twig.config', $configs['twig']);;

    if (!isset($configs['cache.bin']) || empty($configs['cache.bin'])) {
      return;
    }
    foreach (array_keys($container->findTaggedServiceIds('cache.bin')) as $id) {
      $bin = explode('.', $id);
      $bin = array_pop($bin);
      if (in_array($bin, $configs['cache.bin'])) {
        $defaults[$bin] = 'cache.backend.devel_mode_null';
      }
    }
    $container->setParameter('cache_default_bin_backends', $defaults);
  }

}
