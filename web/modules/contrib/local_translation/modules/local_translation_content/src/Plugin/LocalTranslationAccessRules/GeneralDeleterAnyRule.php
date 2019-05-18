<?php

namespace Drupal\local_translation_content\Plugin\LocalTranslationAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class GeneralDeleterAnyRule.
 *
 * @package Drupal\local_translation_content\Plugin\LocalTranslationAccessRules
 *
 * @LocalTranslationAccessRule("local_translation_content_general_deleter_any")
 */
class GeneralDeleterAnyRule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $allowOriginal = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function addDynamicPermissions(ContentEntityInterface $entity) {
    $this->permissions[] = "local_translation_content delete any {$entity->bundle()} content";
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL) {
    if ($operation !== 'delete') {
      return FALSE;
    }
    // Allow plugins to additionally specify dynamic permissions.
    $this->addDynamicPermissions($entity);
    // Check for translation skills only if the limited property is TRUE.
    if ($this->limited) {
      // Fallback for a non-specified language.
      $this->languageFallback($langcode);

      // If user hasn't registered skill for this language - deny access.
      if (!$this->userSkills->userHasSkill($langcode)) {
        return FALSE;
      }
    }

    // If the user doesn't have at least one of the permission from list -
    // deny the access.
    foreach ($this->permissions as $permission) {
      if (!$this->currentUser->hasPermission($permission)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
