<?php

namespace Drupal\fillpdf\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\fillpdf\FillPdfContextManagerInterface;

class FillPdfContextManager implements FillPdfContextManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *
   * The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * Constructs FillPdfContextManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntities(array $context) {
    $entities = [];

    foreach ($context['entity_ids'] as $entity_type => $entity_ids) {
      $type_controller = $this->entityTypeManager->getStorage($entity_type);
      $entity_list = $type_controller->loadMultiple($entity_ids);

      if (!empty($entity_list)) {
        // Initialize array.
        $entities += [$entity_type => []];
        $entities[$entity_type] += $entity_list;
      }
    }

    return $entities;
  }

}
