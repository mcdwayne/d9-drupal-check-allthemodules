<?php

namespace Drupal\navigation_blocks\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides entity back button block definitions for each entity type.
 *
 * @package Drupal\navigation_blocks\Plugin\Deriver
 */
class EntityCanonicalBackButtonDeriver extends EntityBackButtonDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getAdminLabel(EntityTypeInterface $entity_type): TranslatableMarkup {
    return $this->t('Entity Canonical Back Button (@label)', ['@label' => $entity_type->getLabel()]);
  }

}
