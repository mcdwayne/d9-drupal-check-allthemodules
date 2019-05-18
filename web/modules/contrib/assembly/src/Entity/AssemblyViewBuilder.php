<?php

namespace Drupal\assembly\Entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;


class AssemblyViewBuilder extends EntityViewBuilder {
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $service = \Drupal::service('plugin.manager.assembly_build');
    $plugins = $service->getDefinitions();
    $build['#parent'] = $this->getContext($entity);

    foreach ($plugins as $plugin) {
      if (in_array($entity->bundle(), $plugin['types'])) {
        $service->createInstance($plugin['id'])->build($build, $entity, $display, $view_mode);
      }
    }
  }

  private function getContext($entity) {
    if (!isset($entity->_referringItem)) {
      return FALSE;
    }

    // Get the entity reference
    $ref = $entity->_referringItem;

    // Get info about the field
    $field = $ref->getParent();
    $field_name = $field->getName();

    // Get the entity referencing the assembly
    $parent = $ref->getEntity();
    $parent_type = $parent->getEntityTypeId();

    return ['entity' => $parent, 'entity_type' => $parent_type, 'field_name' => $field_name];
  }
}
