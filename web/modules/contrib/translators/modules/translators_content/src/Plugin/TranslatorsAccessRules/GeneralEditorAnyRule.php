<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class GeneralEditorAnyRule.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 *
 * @TranslatorsAccessRule("translators_content_general_editor_any")
 */
class GeneralEditorAnyRule extends AccessRuleBase {

  /**
   * {@inheritdoc}
   */
  protected $allowOriginal = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function addDynamicPermissions(ContentEntityInterface $entity) {
    $this->permissions[] = "translators_content edit any {$entity->bundle()} content";
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL) {
    if ($operation !== 'edit' && $operation !== 'update') {
      return FALSE;
    }

    // Allow plugins to additionally specify dynamic permissions.
    $this->addDynamicPermissions($entity);
    // Check for translation skills only if the limited property is TRUE.
    if ($this->limited) {
      // Fallback for a non-specified language.
      $this->languageFallback($langcode);

      // If user hasn't registered skill for this language - deny access.
      if (!$this->translatorSkills->hasSkill($langcode)) {
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
