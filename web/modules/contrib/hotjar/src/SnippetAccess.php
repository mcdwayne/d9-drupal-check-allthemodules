<?php

namespace Drupal\hotjar;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SnippetAccess.
 *
 * @package Drupal\hotjar
 */
class SnippetAccess implements SnippetAccessInterface, ContainerInjectionInterface {

  const ACCESS_ALLOW = TRUE;
  const ACCESS_DENY = FALSE;
  const ACCESS_IGNORE = NULL;

  /**
   * Hotjar settings.
   *
   * @var \Drupal\hotjar\HotjarSettingsInterface
   */
  protected $settings;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Page match.
   *
   * @var bool
   */
  protected $pageMatch;

  /**
   * SnippetAccess constructor.
   *
   * @param \Drupal\hotjar\HotjarSettingsInterface $hotjar_settings
   *   Hotjar settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   Current path.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   Alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Path matcher.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(
    HotjarSettingsInterface $hotjar_settings,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    CurrentPathStack $current_path,
    AliasManagerInterface $alias_manager,
    PathMatcherInterface $path_matcher,
    AccountInterface $current_user,
    RequestStack $request_stack
  ) {
    $this->settings = $hotjar_settings;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->currentPath = $current_path;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hotjar.settings'),
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('path.current'),
      $container->get('path.alias_manager'),
      $container->get('path.matcher'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    if (!$this->settings->getSetting('account')) {
      return FALSE;
    }

    $result = AccessResult::neutral();

    $result->andIf($this->statusCheckResult());
    $result->andIf($this->pathCheckResult());
    $result->andIf($this->roleCheck());
    $result->andIf($this->cookieConstentCheck());

    $access = [];
    foreach ($this->moduleHandler->getImplementations('hotjar_access') as $module) {
      $module_result = $this->moduleHandler->invoke($module, 'hotjar_access');
      if (is_bool($module_result)) {
        $access[$module] = $module_result;
      }
      elseif ($module_result instanceof AccessResult) {
        $access[$module] = !$module_result->isForbidden();
      }
    }

    $this->moduleHandler->alter('hotjar_access', $access);

    foreach ($access as $module_result) {
      if (is_bool($module_result)) {
        $result->andIf(AccessResult::forbiddenIf(!$module_result));
      }
      elseif ($module_result instanceof AccessResult) {
        $result->andIf($module_result);
      }
    }

    return !$result->isForbidden();
  }

  /**
   * Check HTTP status.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Result.
   */
  protected function statusCheckResult() {
    $request = $this->requestStack->getCurrentRequest();
    $status = NULL;
    if ($exception = $request->attributes->get('exception')) {
      $status = $exception->getStatusCode();
    }
    $not_tracked_status_codes = [
      '403',
      '404',
    ];
    $result = !in_array($status, $not_tracked_status_codes);
    return AccessResult::forbiddenIf(!$result);
  }

  /**
   * Check path.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Path
   */
  protected function pathCheckResult() {
    if (!isset($this->pageMatch)) {
      $visibility = $this->settings->getSetting('visibility_pages');
      $setting_pages = $this->settings->getSetting('pages');

      if (!$setting_pages) {
        $this->pageMatch = TRUE;
        return AccessResult::allowed();
      }

      $pages = mb_strtolower($setting_pages);
      if ($visibility < 2) {
        $path = $this->currentPath->getPath();
        $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));
        $path_match = $this->pathMatcher->matchPath($path_alias, $pages);
        $alias_match = (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages));
        $this->pageMatch = $path_match || $alias_match;

        // When $visibility has a value of 0, the tracking code is displayed on
        // all pages except those listed in $pages. When set to 1, it
        // is displayed only on those pages listed in $pages.
        $this->pageMatch = !($visibility xor $this->pageMatch);
      }
      else {
        $this->pageMatch = FALSE;
      }
    }

    return AccessResult::forbiddenIf(!$this->pageMatch);
  }

  /**
   * Check Hotjar code should be added for user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Result.
   */
  protected function roleCheck() {
    $visibility = $this->settings->getSetting('visibility_roles');
    $enabled = $visibility;
    $roles = $this->settings->getSetting('roles');

    // The hotjar_roles stores the selected roles as an array where
    // the keys are the role IDs. When the role is not selected the
    // value is 0. If a role is selected the value is the role ID.
    $checked_roles = array_filter($roles);
    if (empty($checked_roles)) {
      // No role is selected for tracking, therefore all roles be tracked.
      return AccessResult::allowed();
    }

    if (count(array_intersect($this->currentUser->getRoles(), $checked_roles))) {
      $enabled = !$visibility;
    }

    return AccessResult::forbiddenIf(!$enabled);
  }

  /**
   * Check cookie constent settings.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Result.
   */
  protected function cookieConstentCheck() {
    if ($this->moduleHandler->moduleExists('eu_cookie_compliance')) {
      $config = $this->configFactory->get('eu_cookie_compliance.settings');
      $disabled_javascripts = $config->get('disabled_javascripts');
      $disabled_javascripts = _eu_cookie_compliance_explode_multiple_lines($disabled_javascripts);
      if (in_array('sites/default/files/hotjar/hotjar.script.js', $disabled_javascripts)) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::neutral();
  }

}
