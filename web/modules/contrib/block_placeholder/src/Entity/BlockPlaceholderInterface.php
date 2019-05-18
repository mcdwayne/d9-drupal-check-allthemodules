<?php

namespace Drupal\block_placeholder\Entity;

/**
 * Define block placeholder interface.
 */
interface BlockPlaceholderInterface {

  /**
   * Get block types.
   *
   * @return array
   */
  public function blockTypes();

  /**
   * Referenced limited type.
   *
   * @return string
   */
  public function referenceLimitType();

  /**
   * Referenced limited value.
   *
   * @return int
   */
  public function referencedLimitedValue();

  /**
   * Load block content that's related to this placeholder.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadReferences(array $exclude_ids);

  /**
   * Determine if block placeholder met reference limit.
   *
   * @param array $exclude_ids
   *   An array of excluded entity ids.
   *
   * @return bool
   */
  public function hasReferenceMetLimit(array $exclude_ids = []);

  /**
   * Block reference invalid block types.
   *
   * @return array
   */
  public function invalidBlockTypes();

  /**
   * Get block placeholder associated blocks.
   *
   * @return array
   */
  public function getPlaceholderBlocks();

  /**
   * Get block placeholder reference count.
   *
   * @return int
   */
  public function getReferenceCount();

  /**
   * Determine if an entity exist.
   *
   * @param $id
   *   An entity identifier.
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function entityExist($id);

}
