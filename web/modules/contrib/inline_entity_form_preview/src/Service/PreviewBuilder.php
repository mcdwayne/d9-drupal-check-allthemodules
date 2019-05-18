<?php
/**
 * @file:
 *   Contains Drupal\inline_entity_form_preview\Service\PreviewBuilder.
 */

namespace Drupal\inline_entity_form_preview\Service;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class Preview
 *
 * @package inline_entity_form_preview
 */
class PreviewBuilder implements PreviewBuilderInterface {

  use DependencySerializationTrait;
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an PreviewBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {

    // Use the preview mode if the entity hasn't been saved yet.
    if (empty($entity->id())) {
      $entity->in_preview = TRUE;
    }

    // Get the appropriate view builder.
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());

    // Pass the call off to the view builder.
    return $view_builder->view($entity, $view_mode, $langcode);
  }
}
