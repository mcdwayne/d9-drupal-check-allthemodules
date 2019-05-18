<?php

namespace Drupal\register_display;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class RegisterDisplayServices.
 *
 * Provides services for register display module.
 *
 * @package Drupal\register_display
 */
class RegisterDisplayServices {
  const REGISTER_DISPLAY_BASE_REGISTER_PATH = '/register-display';
  protected $entityTypeManager;
  protected $aliasStorage;
  protected $languageManager;
  protected $configFactory;
  protected $entityDisplayRepository;
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AliasStorageInterface $aliasStorage,
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
    RouteBuilderInterface $routeBuilder) {

    $this->entityTypeManager = $entityTypeManager;
    $this->aliasStorage = $aliasStorage;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * Get register display base path.
   *
   * @return string
   *   Base path.
   */
  public function getRegisterDisplayBasePath() {
    return self::REGISTER_DISPLAY_BASE_REGISTER_PATH;
  }

  /**
   * Function to get available roles to register.
   *
   * @return array|bool
   *   List of available roles to register or FALSE if no roles available.
   */
  public function getAvailableUserRolesToRegister() {
    // We exclude Anonymous role by passing TRUE for user_role_names function.
    $allowedRoles = user_role_names(TRUE);

    // Now we exclude the authenticated role.
    unset($allowedRoles[AccountInterface::AUTHENTICATED_ROLE]);

    // By query entity user_role data we check if any role marked as admin.
    $rolesStorage = $this->entityTypeManager->getStorage('user_role');
    $adminRole = $rolesStorage->getQuery()
      ->condition('is_admin', TRUE)
      ->execute();
    if ($adminRole) {
      unset($allowedRoles[key($adminRole)]);
    }

    return empty($allowedRoles) ? FALSE : $allowedRoles;
  }

  /**
   * Get configuration for register pages.
   *
   * @param string $roleMachineName
   *   Role machine name, If provided function will return register page for
   *   that role only.
   *
   * @return mixed
   *   If registration forms exists, array of paths.
   *   In other situation - FALSE.
   */
  public function getRegistrationPages($roleMachineName = NULL) {
    $availableRoles = $this->getAvailableUserRolesToRegister();
    // If no available roles at all we return false.
    if (!$availableRoles) {
      return FALSE;
    }
    // Loading pages config.
    $pagesConfig = $this->configFactory->get('register_display.settings')->get('pages');
    // If config requested for one only role we return it.
    if (!empty($availableRoles[$roleMachineName])) {
      return $pagesConfig[$roleMachineName];
    }

    return $pagesConfig;
  }

  /**
   * Build array of available pages as options.
   *
   * @return array|mixed
   *   False if no page available, or array of key => option.
   */
  public function getRegistrationPagesOptions() {
    $registrationPages = self::getRegistrationPages();
    if (!$registrationPages) {
      return $registrationPages;
    }
    $registrationPagesOptions = [];
    foreach ($registrationPages as $roleId => $config) {
      $registrationPagesOptions[$roleId] = $config['roleName'] . ' => ' . $config['registerPageAlias'];
    }
    return $registrationPagesOptions;
  }

  /**
   * Wrapper to check if alias exist.
   *
   * @param string $alias
   *   Alias to check.
   *
   * @return bool
   *   True if alias exist, otherwise FALSE.
   */
  public function isAliasExist($alias) {
    return $this->aliasStorage->aliasExists($alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * Update alias.
   *
   * @param string $source
   *   Source path.
   * @param string $alias
   *   Alias path.
   */
  public function updateAlias($source, $alias) {
    // First we check if source has alias.
    $lookupAlias = $this->aliasStorage->lookupPathAlias($source, LanguageInterface::LANGCODE_NOT_SPECIFIED);
    if ($lookupAlias) {
      // Delete old alias.
      $this->aliasStorage->delete(['source' => $source, 'alias' => $lookupAlias]);
    }
    // Create new alias.
    $this->aliasStorage->save($source, $alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * Delete alias by source.
   *
   * @param string $source
   *   Source path.
   */
  public function deleteAliasBySource($source) {
    $this->aliasStorage->delete(['source' => $source]);
  }

  /**
   * Inject user role in the creation process.
   *
   * @param array $form
   *   Register form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addRoleToUser(array &$form, FormStateInterface $form_state) {
    $roleId = $form_state->getValue('roleId');
    $form_state->setValue(['roles', $roleId], $roleId);
  }

  /**
   * Delete register display page.
   *
   * @param string $roleId
   *   Role Id.
   */
  public function deleteRegisterDisplayPage($roleId) {
    $registerPages = self::getRegistrationPages();
    if (array_key_exists($roleId, $registerPages)) {
      // Delete any Alias for register page.
      $registerPageSource = self::getRegisterDisplayBasePath() . '/' . $roleId;
      self::deleteAliasBySource($registerPageSource);
      // Delete configuration.
      unset($registerPages[$roleId]);
      $updateConfig = $this->configFactory->getEditable('register_display.settings')
        ->set('pages', $registerPages);
      // We need to check if this  was used to redirect original register page.
      $redirectTarget = self::getRedirectTarget();
      if ($redirectTarget == $roleId) {
        $updateConfig->clear('isRedirect')->clear('redirectTarget');
      }
      $updateConfig->save();
      self::clearRouteCache();
    }
  }

  /**
   * Get user form mode displays as option.
   *
   * @return array
   *   Key => Value of user form mode.
   */
  public function getUserFormModeOptions() {
    return $this->entityDisplayRepository->getFormModeOptions('user');
  }

  /**
   * Wrapper function for isRedirect config.
   *
   * @return bool|null
   *   Value of isRedirect.
   */
  public function isRedirectDefaultRegisterPage() {
    return $this->configFactory->getEditable('register_display.settings')->get('isRedirect');
  }

  /**
   * Wrapper function for redirectTarget config.
   *
   * @return string
   *   Role id.
   */
  public function getRedirectTarget() {
    return $this->configFactory->getEditable('register_display.settings')->get('redirectTarget');
  }

  /**
   * Clear route cache.
   */
  public function clearRouteCache() {
    $this->routeBuilder->rebuild();
  }

}
