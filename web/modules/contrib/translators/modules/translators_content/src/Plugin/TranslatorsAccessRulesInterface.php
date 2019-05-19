<?php

namespace Drupal\translators_content\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface TranslatorsAccessRulesInterface.
 *
 * @package Drupal\translators_content\Plugin
 */
interface TranslatorsAccessRulesInterface {

  /**
   * Determine weather current user's permitted to perform a specific operation.
   *
   * @param string $operation
   *   Operation name.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity object.
   * @param string|null $langcode
   *   Language ID.
   *
   * @return bool
   *   Access checking result.
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL);

}
