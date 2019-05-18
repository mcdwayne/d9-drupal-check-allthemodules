<?php

namespace Drupal\local_translation_content\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface LocalTranslationAccessRulesInterface.
 *
 * @package Drupal\local_translation_content\Plugin
 */
interface LocalTranslationAccessRulesInterface {

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
