<?php

namespace Drupal\navigation_blocks\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides a 'EntityReferenceBackButton' block.
 *
 * @Block(
 *  id = "reversed_entity_reference_back_button",
 *  deriver = "Drupal\navigation_blocks\Plugin\Deriver\ReversedEntityBackButtonDeriver"
 * )
 */
class ReversedEntityReferenceBackButton extends EntityReferenceBackButtonBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityReferenceOptions(EntityTypeInterface $entity_type): array {
    return $this->entityButtonManager->getReversedEntityReferenceFieldOptions($entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getReferencedEntity(): EntityInterface {
    return $this->entityButtonManager->getReversedEntityReferenceEntity($this->getContextValue('entity'), ...\explode(':', $this->getConfiguration()['entity_reference_field']));
  }

}
