<?php

namespace Drupal\group_behavior;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupContentTypeInterface;

class EntityHooks {

  protected static $preventDeleteRecursion = FALSE;

  /**
   * Post insert.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function insert(EntityInterface $entity) {
    self::createGroupsIfNecessary($entity);
  }

  /**
   * Post update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function update(EntityInterface $entity) {
    if ($entity instanceof ContentEntityInterface) {
      self::createGroupsIfNecessary($entity);
      if ($groupContents = GroupContent::loadByEntity($entity)) {
        /** @var \Drupal\group\Entity\GroupContentInterface[] $groupContents */
        foreach ($groupContents as $groupContent) {
          $groupContentType = $groupContent->getGroupContentType();
          if (static::groupContentTypeHasSetting('autoupdate_title', $groupContentType)) {
            $groupContent->set('label', $entity->label());
            $groupContent->save();
            $group = $groupContent->getGroup();
            $group->set('label', $entity->label());
            $group->save();
          }
        }
      }
    }
  }

  /**
   * Post delete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public static function delete(EntityInterface $entity) {
    // Note that deleting content will delete the GroupContent relation via
    // group_entity_delete(), and deleting the group will delete the
    // GroupContent relation via \Drupal\group\Entity\Group::preDelete.
    $groups = [];
    if ($entity instanceof ContentEntityInterface) {
      if ($groupContents = GroupContent::loadByEntity($entity)) {
        /** @var \Drupal\group\Entity\GroupContentInterface[] $groupContents */
        foreach ($groupContents as $groupContent) {
          $groupContentType = $groupContent->getGroupContentType();
          if (static::groupContentTypeHasSetting('autodelete', $groupContentType)) {
            $groups[] = $groupContent->getGroup();
          }
        }
      }
    }

    // We knocked that out in group_behavior_module_implements_alter().
    \group_entity_delete($entity);

    foreach ($groups as $group) {
      // This will delete more GroupContent relations, but - if for heavens sake
      // noone else messes with this - not any group content itself.
      $group->delete();
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected static function createGroupsIfNecessary(EntityInterface $entity) {
    $groupContentTypes = self::getApplicableGroupContentTypes('autocreate', $entity);
    foreach ($groupContentTypes as $groupContentType) {
      if (!self::entityGroupContentOfType($entity, $groupContentType)) {
        $group = Group::create([
          'type' => $groupContentType->getGroupTypeId(),
          'label' => $entity->label(),
        ]);
        $group->save();
        $groupContent = GroupContent::create([
          'type' => $groupContentType->id(),
          'gid' => $group->id(),
          'entity_id' => $entity->id(),
          'label' => $entity->label(),
        ]);
        $groupContent->save();
      }
    }
  }

  /**
   * Filter group content by type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\group\Entity\GroupContentTypeInterface $groupContentType
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   */
  protected static function entityGroupContentOfType(EntityInterface $entity, GroupContentTypeInterface $groupContentType) {
    $entityGroupContents = Groupcontent::loadByEntity($entity);
    $groupContentTypeId = $groupContentType->id();
    return array_filter($entityGroupContents, function (GroupContentInterface $groupContent) use ($groupContentTypeId) {
      return $groupContent->getGroupContentType()->id() === $groupContentTypeId;
    });
  }

  /**
   * Get applicable group content types.
   *
   * @param string $setting
   *   The setting to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   Group content types.
   */
  protected static function getApplicableGroupContentTypes($setting, EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $groupContentTypes */
    $groupContentTypes = GroupContentType::loadByEntityTypeId($entity->getEntityTypeId());
    $groupContentTypes = self::filterGroupContentTypesBySetting($groupContentTypes, $setting);
    $groupContentTypes = self::filterGroupContentTypesByBundle($groupContentTypes, $entity->bundle());
    return $groupContentTypes;
  }

  /**
   * Filter group content types by setting.
   *
   * @param \Drupal\group\Entity\GroupContentTypeInterface[] $groupContentTypes
   *   Group content types.
   * @param string $setting
   *   The setting to check.
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   Group content types.
   */
  protected static function filterGroupContentTypesBySetting($groupContentTypes, $setting) {
    $applicableGroupContentTypes = [];
    foreach ($groupContentTypes as $id => $groupContentType) {
      if (self::groupContentTypeHasSetting($setting, $groupContentType)) {
        $applicableGroupContentTypes[$id] = $groupContentType;
      }
    }
    return $applicableGroupContentTypes;
  }

  /**
   * @param $setting
   * @param $groupContentType
   * @return mixed
   */
  protected static function groupContentTypeHasSetting($setting, $groupContentType) {
    return $groupContentType->getThirdPartySetting('group_behavior', $setting);
  }

  /**
   * @param \Drupal\group\Entity\GroupContentTypeInterface[] $groupContentTypes
   *   Group content types.
   * @param string $bundle
   *   The bundle.
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   Group content types.
   */
  protected static function filterGroupContentTypesByBundle($groupContentTypes, $bundle) {
    $applicableGroupContentTypes = [];
    foreach ($groupContentTypes as $id => $groupContentType) {
      if ($bundle === $groupContentType->getContentPlugin()->getEntityBundle()) {
        $applicableGroupContentTypes[$id] = $groupContentType;
      }
    }
    return $applicableGroupContentTypes;
  }

}
