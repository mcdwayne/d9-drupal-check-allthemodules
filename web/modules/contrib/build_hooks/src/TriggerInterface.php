<?php

namespace Drupal\build_hooks;

use Drupal\build_hooks\Entity\FrontendEnvironmentInterface;

/**
 * Interface TriggerInterface.
 */
interface TriggerInterface {

  /**
   * Deploy frontend environments.
   *
   * @return mixed
   *   Mixed.
   */
  public function deployFrontendCronEnvironments();

  /**
   * Triggers a build hook for an environment.
   *
   * @param \Drupal\build_hooks\Entity\FrontendEnvironmentInterface $frontend_environment
   *   The Environment to trigger the deployment for.
   *
   * @return mixed
   *   Mixed.
   */
  public function triggerBuildHookForEnvironment(FrontendEnvironmentInterface $frontend_environment);

  /**
   * Whether to show the menu.
   */
  public function showMenu();

  /**
   * Utility function to retrieve the cache tag to apply to the toolbar.
   *
   * @return string
   *   The toolbar cache tag.
   */
  public function getToolbarCacheTag();

  /**
   * Invalidates the toolbar cache tag.
   */
  public function invalidateToolbarCacheTag();

}
