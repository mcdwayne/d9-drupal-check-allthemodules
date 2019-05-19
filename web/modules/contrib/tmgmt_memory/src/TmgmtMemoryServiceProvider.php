<?php

namespace Drupal\tmgmt_memory;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for TMGMT Memory.
 */
class TmgmtMemoryServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('tmgmt.segmenter');
    $definition->setClass('Drupal\tmgmt_memory\Segmenter');
  }
}
