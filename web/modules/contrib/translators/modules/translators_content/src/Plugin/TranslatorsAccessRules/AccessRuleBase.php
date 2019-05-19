<?php

namespace Drupal\translators_content\Plugin\TranslatorsAccessRules;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\translators\Services\TranslatorSkills;
use Drupal\translators_content\Plugin\TranslatorsAccessRulesInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AccessRuleBase.
 *
 * Basic abstract class for all access rules plugins.
 *
 * @package Drupal\translators_content\Plugin\TranslatorsAccessRules
 */
abstract class AccessRuleBase extends PluginBase implements TranslatorsAccessRulesInterface, ContainerFactoryPluginInterface {

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * User skills service.
   *
   * @var \Drupal\translators\Services\TranslatorSkills
   */
  protected $translatorSkills;
  /**
   * Array of permissions to be checked per rule.
   *
   * @var array
   */
  protected $permissions = [];
  /**
   * Flag that determines if the user should be limited to translation skills.
   *
   * @var bool
   */
  protected $limited = TRUE;
  /**
   * Flag that determines if the user is allowed to operate on original entity.
   *
   * @var bool
   */
  protected $allowOriginal = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $manager,
    TranslatorSkills $translatorSkills
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser      = $current_user;
    $this->translatorSkills = $translatorSkills;
    $this->languageManager  = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('current_user'),
      $container->get('language_manager'),
      $container->get('translators.skills')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($operation, ContentEntityInterface $entity, $langcode = NULL) {
    // Fallback for a non-specified language.
    $this->languageFallback($langcode);

    // Prevent allowing to manage original language.
    // Leave this for the content's permissions.
    if (!$this->allowOriginal && $this->isOriginal($entity, $langcode)) {
      return FALSE;
    }
    // Allow plugins to additionally specify dynamic permissions.
    $this->addDynamicPermissions($entity);
    // Check for translation skills only if the limited property is TRUE.
    if ($this->limited && !$this->translatorSkills->hasSkill($langcode)) {
      // If user hasn't registered skill for this language - deny access.
      return FALSE;
    }

    // If the user doesn't have at least one of the permission from list -
    // deny the access.
    foreach ($this->permissions as $permission) {
      if (!$this->currentUser->hasPermission($permission)) {
        return FALSE;
      }
    }

    if (!$this->currentUser->hasPermission("$operation content translations")
      && !$this->currentUser->hasPermission("translators_content $operation content translations")
    ) {
      return FALSE;
    }

    // Everything is fine - allow access.
    return TRUE;
  }

  /**
   * Additionally adds dynamic options.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to be operated on.
   */
  protected function addDynamicPermissions(ContentEntityInterface $entity) {
    // Empty method by default.
  }

  /**
   * Check whether the current translation is the original entity language.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Processing entity object.
   * @param string|null $langcode
   *   Language ID.
   *
   * @return bool
   *   TRUE - if the current translation is the original entity language,
   *   FALSE otherwise.
   */
  protected function isOriginal(ContentEntityInterface $entity, $langcode = NULL) {
    return $entity->getUntranslated()->language()->getId() === $langcode;
  }

  /**
   * Fallback for a non-specified language.
   *
   * @param string|null &$langcode
   *   Language ID.
   */
  protected function languageFallback(&$langcode = NULL) {
    if (is_null($langcode)) {
      $langcode = $this->languageManager
        ->getCurrentLanguage()
        ->getId();
    }
  }

  /**
   * Check if current user is the owner of processing entity.
   *
   * @param \Drupal\user\EntityOwnerInterface $entity
   *   Processing entity.
   *
   * @return bool
   *   TRUE - if current user is the owner, FALSE otherwise.
   */
  protected function isOwner(EntityOwnerInterface $entity) {
    return $this->currentUser->id() === $entity->getOwnerId();
  }

}
