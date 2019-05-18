<?php

namespace Drupal\navigation_blocks\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides entity reference back button block definitions for each entity type.
 *
 * @package Drupal\navigation_blocks\Plugin\Deriver
 */
class EntityReferenceBackButtonDeriver extends EntityBackButtonDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getAdminLabel(EntityTypeInterface $entity_type): TranslatableMarkup {
    return $this->t('Entity Reference Back Button (@label)', ['@label' => $entity_type->getLabel()]);
  }

}
