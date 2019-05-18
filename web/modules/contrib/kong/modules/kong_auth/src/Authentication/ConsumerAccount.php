<?php

namespace Drupal\kong_auth\Authentication;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConsumerAccount.
 */
class ConsumerAccount implements ConsumerAccountInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language default service.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * Stores the current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Stores the role storage object.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a new ConsumerAccount object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The language default.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageDefault $language_default) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageDefault = $language_default;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->request->headers->get('X-Consumer-Custom-ID');
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    $roles = [];

    if ($this->request->headers->has('X-Consumer-Groups')) {
      $roles += preg_split("/,\s?/", $this->request->headers->get('X-Consumer-Groups'));
    }

    if (!$exclude_locked_roles) {
      $roles[] = RoleInterface::AUTHENTICATED_ID;
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->getRoleStorage()
      ->isPermissionInRoles($permission, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return !$this->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->request->headers->get('X-Anonymous-Consumer', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($fallback_to_default = TRUE) {
    return $fallback_to_default ? $this->languageDefault->get()->getId() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    return $this->getPreferredLangcode($fallback_to_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->getAccountName();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountName() {
    return $this->request->headers->get('X-Consumer-Username');
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName() {
    return $this->getAccountName();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return empty($this->request) ? 0 : $this->request->server->get('REQUEST_TIME');
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\user\RoleStorageInterface
   *   The role storage object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getRoleStorage() {
    if (empty($this->roleStorage)) {
      $this->roleStorage = $this->entityTypeManager->getStorage('user_role');
    }

    return $this->roleStorage;
  }

}
