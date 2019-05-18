<?php

namespace Drupal\block_placeholder;

use Drupal\block_placeholder\Entity\BlockPlaceholderReference;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Define block placeholder manager.
 */
class BlockPlaceholderManager implements BlockPlaceholderManagerInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManger;

  /**
   * Block placeholder manager constructor.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManger = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return $this
      ->entityTypeManger
      ->getStorage('block_placeholder')
      ->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []) {
    return $this->entityTypeManger
      ->getStorage('block_placeholder')
      ->loadMultiple();
  }

  /**
   * Block placeholder reference options.
   *
   * @return array
   */
  public function getOptions($bundle) {
    $options = [];

    foreach ($this->loadMultiple() as $name => $reference) {
      if (!$reference instanceof BlockPlaceholderReference) {
        continue;
      }
      $block_types = $reference->blockTypes();

      // Restrict showing options if bundle hasn't been defined.
      if (!empty($block_types) && !in_array($bundle, $block_types)) {
        continue;
      }

      $options[$name] = $reference->label();
    }

    return $options;
  }
}
