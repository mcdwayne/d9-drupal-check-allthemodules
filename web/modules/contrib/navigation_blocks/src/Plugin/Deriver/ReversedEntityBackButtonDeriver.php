<?php

namespace Drupal\navigation_blocks\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides reversed entity reference definitions for each entity type.
 *
 * @package Drupal\navigation_blocks\Plugin\Deriver
 */
class ReversedEntityBackButtonDeriver extends EntityBackButtonDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getAdminLabel(EntityTypeInterface $entity_type): TranslatableMarkup {
    return $this->t('Reversed Entity Reference Back Button (@label)', ['@label' => $entity_type->getLabel()]);
  }

}
