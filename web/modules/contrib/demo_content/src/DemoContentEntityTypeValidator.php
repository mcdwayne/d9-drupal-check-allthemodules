<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentEntityValidator.
 */

namespace Drupal\demo_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Class DemoContentEntityValidator
 *
 * @package Drupal\demo_content
 */
class DemoContentEntityTypeValidator implements DemoContentEntityTypeValidatorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DemoContentEntityTypeValidator constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @inheritdoc
   */
  public function isContentEntityType($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    // Throw exception if entity_type not found.
    if (!$entity_type) {
      throw new PluginNotFoundException($entity_type_id, sprintf('The "%s" entity type does not exist.', $entity_type_id));
    }

    // Check if entity_type is a content entity i.e implements ContentEntityInterface.
    $reflection = new \ReflectionClass($entity_type->getClass());
    if ($reflection->implementsInterface('\Drupal\Core\Entity\ContentEntityInterface')) {
      return TRUE;
    }

    return FALSE;
  }
}
