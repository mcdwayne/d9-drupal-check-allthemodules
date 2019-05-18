<?php

namespace Drupal\allowed_languages\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Allowed languages access check base class.
 */
abstract class AccessCheckBase implements AccessInterface {

  /**
   * Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * AccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Checks if the user is allowed to translate the specified language.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to check.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language to check for.
   *
   * @return bool
   *   If the user is allowed to or not.
   */
  protected function userIsAllowedToTranslateLanguage(UserInterface $user, LanguageInterface $language) {
    // Bypass the access check if the user has permission to translate all languages.
    if ($user->hasPermission('translate all languages')) {
      return TRUE;
    }

    $allowed_languages = $this->getUsersAllowedLanguages($user);
    return in_array($language->getId(), $allowed_languages);
  }

  /**
   * Get the allowed languages for the specified user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to get allowed languages for.
   *
   * @return \Drupal\language\Entity\ConfigurableLanguage[]
   *   An array of language entities.
   */
  protected function getUsersAllowedLanguages(UserInterface $user) {
    return allowed_languages_get_allowed_languages_for_user($user);
  }

  /**
   * Loads a user entity based on account proxy object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy used to load the full user entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\user\UserInterface|null
   *   User entity or NULL.
   */
  protected function loadUserEntityFromAccountProxy(AccountInterface $account) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->load($account->id());
  }

}
