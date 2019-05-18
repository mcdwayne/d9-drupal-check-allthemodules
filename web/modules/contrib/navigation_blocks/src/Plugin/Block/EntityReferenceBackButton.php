<?php

namespace Drupal\navigation_blocks\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides a 'EntityReferenceBackButton' block.
 *
 * @Block(
 *  id = "entity_reference_back_button",
 *  deriver = "Drupal\navigation_blocks\Plugin\Deriver\EntityReferenceBackButtonDeriver"
 * )
 */
class EntityReferenceBackButton extends EntityReferenceBackButtonBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityReferenceOptions(EntityTypeInterface $entity_type): array {
    return $this->entityButtonManager->getEntityReferenceFieldOptions($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getReferencedEntity(): EntityInterface {
    return $this->entityButtonManager->getReferencedEntity($this->getContextValue('entity'), $this->getConfiguration()['entity_reference_field']);
  }

}
