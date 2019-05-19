<?php

/**
 * @file
 * Contains \Drupal\simple_currency_converter\Compiler\CurrencyConvertersPass.
 */

namespace Drupal\simple_currency_converter\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds simple_currency_converter_currency_converters parameter to the container.
 */
class CurrencyConvertersPass implements CompilerPassInterface {

  /**
   * Implements CompilerPassInterface::process().
   *
   * Collects the tagged services and stores them into the appropriate parameter.
   */
  public function process(ContainerBuilder $container) {
    $services = [];
    $default = NULL;

    $definitions = $container->findTaggedServiceIds('simple_currency_converter');

    foreach ($definitions as $key => $value) {
      if (is_null($default)) {
        $default = $key;
      }

      if (isset($value[0]['default'])) {
        $default = $key;
      }

      $definition = $container->getDefinition($key);

      $tag = $definition->getTag('simple_currency_converter');

      $services[$key] = [
        'class' => $definition->getClass(),
        'title' => $tag['0']['title'],
      ];
    }

    $container->setParameter('simple_currency_converters', $services);
    $container->setParameter('simple_currency_converter_default', $default);
  }
}
