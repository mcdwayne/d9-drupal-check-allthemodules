<?php

namespace Drupal\entity_pilot;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates a service modifier to add a file entity item normalizer.
 */
class EntityPilotServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // File entity module does something similar, so if it exists - we don't
    // need these services. If it doesn't then we need to create our own.
    // @todo These will no longer be needed when
    //   https://www.drupal.org/node/1927648 is in core.
    if (!isset($modules['file_entity'])) {
      // Add a normalizer service for file entities.
      $service_definition = new Definition('Drupal\entity_pilot\Normalizer\FileEntityNormalizer', [
        new Reference('rest.link_manager'),
        new Reference('entity.manager'),
        new Reference('module_handler'),
      ]);
      // The priority must be higher than that of
      // serializer.normalizer.file_entity.hal in hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => 30]);
      $container->setDefinition('serializer.normalizer.entity.entity_pilot', $service_definition);
    }
    if (isset($modules['menu_link_content'])) {
      // Add a normalizer service for menu-link-content entities.
      $service_definition = new Definition('Drupal\entity_pilot\Normalizer\MenuLinkContentNormalizer', [
        new Reference('rest.link_manager'),
        new Reference('entity.manager'),
        new Reference('module_handler'),
        new Reference('entity_pilot.resolver.unsaved_uuid'),
        new Reference('serializer.normalizer.entity_reference_item.hal'),
      ]);
      // The priority must be higher than that of
      // serializer.normalizer.entity.hal in hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => 50]);
      $container->setDefinition('entity_pilot.normalizer.menu_link_content.hal', $service_definition);
    }
    if (isset($modules['book'])) {
      // Add a normalizer service for book nodes.
      $service_definition = new Definition('Drupal\entity_pilot\Normalizer\BookNormalizer', [
        new Reference('rest.link_manager'),
        new Reference('entity.manager'),
        new Reference('module_handler'),
        new Reference('config.factory'),
      ]);
      // The priority must be higher than that of
      // serializer.normalizer.entity.hal in hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => 45]);
      $container->setDefinition('entity_pilot.normalizer.book.hal', $service_definition);
    }
  }

}
