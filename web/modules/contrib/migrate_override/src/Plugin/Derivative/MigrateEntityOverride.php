<?php

namespace Drupal\migrate_override\Plugin\Derivative;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\Plugin\Derivative\MigrateEntity;
use Drupal\migrate_override\Plugin\migrate\destination\ContentEntityOverride;

/**
 * Class MigrateEntityOverride.
 */
class MigrateEntityOverride extends MigrateEntity {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityDefinitions as $entity_type => $entity_info) {
      if ($entity_info->entityClassImplements(ContentEntityInterface::class)) {
        $this->derivatives[$entity_type] = [
          'id' => "entity_override:$entity_type",
          'class' => ContentEntityOverride::class,
          'requirements_met' => 1,
          'provider' => $entity_info->getProvider(),
        ];
      }
    }
    return $this->derivatives;
  }

}
