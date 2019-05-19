<?php
/**
 * @file
 * Contains \Drupal\simple_currency_converter\SimpleCurrencyConverterServiceProvider.
 */

namespace Drupal\simple_currency_converter;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

use Drupal\simple_currency_converter\Compiler\CurrencyConvertersPass;

class SimpleCurrencyConverterServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new CurrencyConvertersPass());
  }

}
