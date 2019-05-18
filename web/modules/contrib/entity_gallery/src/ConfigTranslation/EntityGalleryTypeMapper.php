<?php

namespace Drupal\entity_gallery\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a configuration mapper for entity gallery types.
 */
class EntityGalleryTypeMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  public function setEntity(ConfigEntityInterface $entity) {
    parent::setEntity($entity);

    // Adds the title label to the translation form.
    $entity_gallery_type = $entity->id();
    $config = $this->configFactory->get("core.base_field_override.entity_gallery.$entity_gallery_type.title");
    if (!$config->isNew()) {
      $this->addConfigName($config->getName());
    }
  }

}
