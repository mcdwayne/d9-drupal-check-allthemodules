<?php

namespace Drupal\allowed_languages\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\user\Entity\User;

/**
 * Base class for entity translation controllers.
 */
class AllowedLanguagesController extends ContentTranslationController {

  /**
   * Override overview method defined in ContentTranslationController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $build = parent::overview($route_match, $entity_type_id);
    $user = $this->currentUser();

    if (!$user->hasPermission('translate all languages') &&
      !empty($build['content_translation_overview']['#rows'])) {
      $rows = &$build['content_translation_overview']['#rows'];
      $languages = $this->languageManager()->getLanguages();
      $user_entity = User::load($user->id());
      $allowed_languages = allowed_languages_get_allowed_languages_for_user($user_entity);
      // Index of a row with the language in the parent output.
      $i = 0;
      // Parent overview() method does the same loop through available languages.
      foreach ($languages as $language) {
        // If the user is not allowed to manage entities in this language.
        if (!in_array($language->getId(), $allowed_languages)) {
          $target_row = $rows[$i];
          // Row with operations will always be the last. See parent method.
          end($target_row);
          $operations_key = key($target_row);
          // Unset operations element in case if user can't edit entities in this language.
          unset($rows[$i][$operations_key]['data']);
        }
        // Increment the language index.
        $i++;
      }
    }

    return $build;
  }

}
