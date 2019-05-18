<?php

namespace Drupal\live_person;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Determines whether or not LiveChat should be embedded on the current page.
 */
class VisibilityCheck {

  /**
   * The devel config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a new VisibilityCheck object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current account.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $account, ModuleHandlerInterface $module_handler, RequestStack $request_stack, PathMatcherInterface $pathMatcher, CurrentPathStack $current_path) {
    $this->config = $config_factory->get('live_person.settings');
    $this->account = $account;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
    $this->pathMatcher = $pathMatcher;
    $this->currentPath = $current_path;
  }

  /**
   * Determines whether or not LiveChat should be embedded on the current page.
   *
   * @return bool
   *   Whether or not the chat should be embedded.
   */
  public function checkVisibility() {
    // Disabling LiveChat overrides other visibility settings.
    // We will get NULL if the settings have not been saved after the `enabled`
    // setting was introduced, and we want to default to TRUE in that case. We
    // don't want to use an update hook because that will change the
    // configuration entity in the database but depending on the site's
    // deployment strategy this setting will be overriden.
    $enabled = $this->config->get('enabled');
    if (!is_null($enabled) && !$enabled) {
      return FALSE;
    }

    // First check visibility against the page and role based visibility settings.
    $visibility = $this->checkPagesVisibility() && $this->checkRolesVisibility();

    // Then allow other modules to alter the visibility for the current request.
    $this->moduleHandler->alter('live_person_visibility', $visibility);
    return $visibility;
  }

  /**
   * Determines whether or not the JS should be added to the current
   * page based on the module's visibility settings.
   *
   * @return bool
   *   Whether or not the chat should be visible on that page.
   */
  protected function checkPagesVisibility() {
    $visibility = $this->config->get('visibility');
    $pages = $this->config->get('pages');

    if (!empty($pages)) {
      $pages = Unicode::strtolower($pages);
      $current_path = $this->currentPath->getPath();
      $path = $current_path === '/' ? $current_path : rtrim($current_path, '/');
      $page_match = $this->pathMatcher->matchPath($path, $pages);
      // When $visibility has a value of 0, LiveChat is embedded on all pages
      // except those listed in $pages. When set to 1, it is displayed only on
      // those pages listed in $pages.
      return !($visibility xor $page_match);
    }
    else {
      return TRUE;
    }
  }

  /**
   * Determines whether or not the JS should be added to the page for a given
   * account based on its user roles.
   *
   * @return bool
   *   Whether or not the chat should be visible on that page for the configured
   *   roles.
   */
  protected function checkRolesVisibility() {
    $roles = array_filter($this->config->get('roles'));

    // If one or more roles are meant to be excluded from LiveChat...
    if (count($roles) > 0) {
      // Look for them in the user's roles array and disable the LiveChat widget
      // if we find any.
      foreach ($this->account->getRoles() as $delta => $role) {
        if (isset($roles[$role]) && $role == $roles[$role]) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
