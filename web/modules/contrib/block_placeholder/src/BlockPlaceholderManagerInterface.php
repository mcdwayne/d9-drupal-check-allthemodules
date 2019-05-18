<?php

namespace Drupal\block_placeholder;

/**
 * Define block placeholder manager interface.
 */
interface BlockPlaceholderManagerInterface {

  /**
   * Load block placeholder.
   *
   * @param $id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function load($id);

  /**
   * Load multiple block placeholders.
   *
   * @param array $ids
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadMultiple(array $ids = []);
}
