<?php

/**
 * @file
 * Contains \Drupal\redirect_deleted_entities\RedirectManagerInterface.
 */

namespace Drupal\redirect_deleted_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides interface for redirect manager.
 */
interface RedirectManagerInterface {

  /**
   * Creates redirect for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to be redirected.
   */
  public function createRedirect(EntityInterface $entity);

  /**
   * Gets redirect for the entity.
   *
   * @param string $entity_type_id
   *   An entity (e.g. node, taxonomy, user, etc.)
   * @param string $bundle
   *   A bundle (e.g. content type, vocabulary ID, etc.)
   * @param string $language
   *   A language code, defaults to the LANGUAGE_NONE constant.
   *
   * @return string
   */
  public function getRedirectByEntity($entity_type_id, $bundle = '', $language = LanguageInterface::LANGCODE_NOT_SPECIFIED);

}
