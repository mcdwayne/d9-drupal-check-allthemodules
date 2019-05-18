<?php

namespace Drupal\groupmediaplus;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;

class GroupMediaPlus {

  /**
   * @param string $path
   * @param bool $extractGroup
   * @param string[] $entityTypeIds
   * @return string[]
   */
  public static function getGroupIdsFromEntityPath($path, $extractGroup = TRUE, $entityTypeIds = NULL) {
    if (!isset($entityTypeIds)) {
      $entityTypeIds = array_keys(self::getAllGroupContentEntityTypeOptions());
    }
    $groupIds = [];
    if ($path) {
      /** @var \Symfony\Component\Routing\RouterInterface $router */
      $router = \Drupal::service('router.no_access_checks');
      $parameters = $router->match($path);
      if ($extractGroup && isset($parameters['group']) && ($group = $parameters['group']) && $group instanceof GroupInterface) {
        $groupId = $group->id();
        $groupIds[$groupId] = $groupId;
      }
      foreach ($entityTypeIds as $entityTypeId) {
        if (isset($parameters[$entityTypeId]) && ($entity = $parameters[$entityTypeId]) && $entity instanceof ContentEntityInterface) {
          /** @var \Drupal\group\Entity\GroupContentInterface $groupContent */
          foreach (GroupContent::loadByEntity($entity) as $groupContent) {
            $groupId = $groupContent->getGroup()->id();
            $groupIds[$groupId] = $groupId;
          }
        }
      }
    }
    return $groupIds;
  }

  public static function getAllGroupContentEntityTypeOptions() {
    $entityTypeIds = [];
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $groupContentType */
    foreach (GroupContentType::loadMultiple() as $groupContentType) {
      $entityTypeId = $groupContentType->getContentPlugin()->getEntityTypeId();
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entityType */
      $entityType = \Drupal::entityTypeManager()->getDefinition($entityTypeId);
      $entityTypeIds[$entityTypeId] = $entityType->getLabel();
    }
    return $entityTypeIds;
  }

}
