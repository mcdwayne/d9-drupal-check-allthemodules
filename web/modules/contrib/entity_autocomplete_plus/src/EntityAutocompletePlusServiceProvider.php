<?php

namespace Drupal\entity_autocomplete_plus;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityAutocompletePlusServiceProvider implements ServiceModifierInterface {

  /**
   * Modifies existing service definitions to override the entity autocomplete matcher
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {

    for ($id = 'entity.autocomplete_matcher'; $container->hasAlias($id); $id = (string) $container->getAlias($id));
    $definition = $container->getDefinition($id);
    $definition->setClass('Drupal\entity_autocomplete_plus\Entity\EntityAutocompletePlusMatcher');
    $definition->setArguments([
        new Reference('plugin.manager.entity_reference_selection'),
        new Reference('entity.manager'),
    ]);
    $container->setDefinition($id, $definition);
  }

}