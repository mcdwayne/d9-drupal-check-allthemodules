<?php

namespace Drupal\entity_generic\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single entity.
 */
class GenericViewController extends EntityViewController {

  /**
   * The _title_callback for the page that renders a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current entity.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $entity) {
    return $this->entityManager->getTranslationFromContext($entity)->label();
  }

}
