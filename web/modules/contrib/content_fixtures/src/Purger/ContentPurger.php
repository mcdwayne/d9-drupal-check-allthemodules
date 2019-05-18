<?php

namespace Drupal\content_fixtures\Purger;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ContentPurger
 */
class ContentPurger implements PurgerInterface {

  /** @var EntityTypeManagerInterface */
  private $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function purge() {
    $contentEntityTypes = $this->getContentEntityTypes();
    foreach ($contentEntityTypes as $entityType) {
      $storage = $this->entityTypeManager->getStorage($entityType);
      $query = $storage->getQuery();

      $toDelete = $query->execute();

      foreach ($toDelete as $id) {
        $entity = $storage->load($id);
        // Some entitiers can be deleted when their parents are deleted if there
        // is a hierarchical structure (eg. taxonomy).
        if ($entity && !$this->isProtected($entity)) {
          $entity->delete();
        }
      }
    }
  }

  /**
  * Content Entity Types that will be cleaned up before the fixtures load.
  *
  * @return array
  */
 protected function getContentEntityTypes() {
   $contentEntityTypes = [];
   $entity_type_definations = $this->entityTypeManager->getDefinitions();
   /* @var $definition EntityTypeInterface */
   foreach ($entity_type_definations as $key => $definition) {
     if ($definition instanceof ContentEntityTypeInterface) {
       $contentEntityTypes[] = $key;
     }
   }

   return $contentEntityTypes;
 }

  /**
   * @inheritdoc
   */
  protected function isProtected(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'user' && $entity->id() <= 1;
  }
}
