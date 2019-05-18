<?php

namespace Drupal\file_ownage;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the stage_file_proxy fetcher manager service.
 *
 * @see https://www.drupal.org/docs/8/api/services-and-dependency-injection/altering-existing-services-providing-dynamic-services
 */
class FileOwnageServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides the way SFP will handle requests to fetch a missing file.
    // @see stage_file_proxy.services.yml
    $definition = $container->getDefinition('stage_file_proxy.fetch_manager');
    $definition->setClass('Drupal\file_ownage\FetchManager');
  }

}
