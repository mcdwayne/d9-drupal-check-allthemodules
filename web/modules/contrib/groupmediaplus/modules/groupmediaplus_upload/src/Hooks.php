<?php

namespace Drupal\groupmediaplus_upload;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupContentStorage;
use Drupal\groupmediaplus\GroupMediaPlus;
use Drupal\media\MediaInterface;

class Hooks {

  public static function uploadFormAlter(&$form, FormStateInterface $formState, $formId) {
    $alterThisForm = in_array($formId, ['media_library_upload_form'])
      || 'entity_browser_' === substr($formId, 0, 15);
    if (!$alterThisForm) {
      return;
    }
    $path = \Drupal::request()->query->get('original_path');
    // Save the entity's groups to form state.
    // @todo Care for new entities, hack it like paragraphs storage.
    if ($groupIds = GroupMediaPlus::getGroupIdsFromEntityPath($path)) {
      $formState->set('groupmediaplus_upload_groups', $groupIds);
      // As we have no suitable hook, we use this hack.
      array_unshift($form['#submit'], [SubmitStatus::class, 'on']);
      array_push($form['#submit'], [SubmitStatus::class, 'off']);
    }
  }

  public static function entityInsert(EntityInterface $entity) {
    if ($entity instanceof MediaInterface && ($formState = SubmitStatus::getFormState())) {
      self::submitMedia($entity, $formState);
    }
  }

  public static function submitMedia(MediaInterface $media, FormStateInterface $formState) {
    foreach ((array)$formState->get('groupmediaplus_upload_groups') as $groupId) {
      if ($groupContentType = self::getApplicableGroupContentType($media, Group::load($groupId))) {
        $groupContent = GroupContent::create([
          'type' => $groupContentType->id(),
          'gid' => $groupId,
          'entity_id' => $media->id(),
          'label' => $media->label(),
        ]);
        $groupContent->save();
      }
    }
  }

  /**
   * Get applicable group content type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   * @return \Drupal\group\Entity\GroupContentTypeInterface|null
   *   The (unique) group content type, or null.
   */
  protected static function getApplicableGroupContentType(EntityInterface $entity, GroupInterface $group) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $groupContentType */
    foreach (GroupContentType::loadByEntityTypeId($entity->getEntityTypeId()) as $groupContentType) {
      if (
        $groupContentType->getGroupTypeId() === $group->getGroupType()->id()
        && $groupContentType->getContentPlugin()->getEntityBundle() === $entity->bundle()
      ) {
        return $groupContentType;
      }
    }
    return NULL;
  }

}
