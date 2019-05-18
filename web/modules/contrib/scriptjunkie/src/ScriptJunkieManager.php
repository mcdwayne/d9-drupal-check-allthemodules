<?php

namespace Drupal\scriptjunkie;

use Drupal\Core\Path\PathMatcherInterface;

/**
 * Provides a helper manager class for various scriptjunkie tasks.
 */
class ScriptJunkieManager {

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * An array of paths that were already checked and their match status.
   *
   * @var array
   */
  protected $matches = [];

  /**
   * Constructs a new NgLightbox service.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Patch matcher services for comparing the lightbox patterns.
   */
  public function __construct(PathMatcherInterface $path_matcher) {
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Check script page visibility.
   *
   * Based on visibility setting this function returns TRUE if scriptjunkie code
   * should be added to the current page and otherwise FALSE.
   *
   * @param array $script
   *   The script to check for.
   *
   * @return bool
   *   TRUE if the Script can be added to the current page. FALSE if not.
   */
  public function checkPageVisibility($script) {
    $page_match = TRUE;
    $visibility = (int) $script['pages']['mode'];
    $pages = $script['pages']['list'];

    // Match path if necessary.
    if (!empty($pages)) {
      if ($visibility < 2) {
        $path = \Drupal::service('path.current')->getPath();
        $page_match = $this->pathMatcher->matchPath($path, $pages);

        // When $visibility has a value of 0, the script is displayed on
        // all pages except those listed in $pages. When set to 1, it
        // is displayed only on those pages listed in $pages.
        $page_match = !($visibility xor $page_match);
      }
      else {
        if (function_exists('php_eval')) {
          $page_match = php_eval($pages);
        }
        else {
          $page_match = FALSE;
          \Drupal::logger('scriptjunkie')
            ->error('The php_eval function does not exist. Need to install the PHP module.');
        }
      }
    }
    else {
      $page_match = TRUE;
    }
    return $page_match;
  }

  /**
   * Check user script visibility.
   *
   * Tracking visibility check for an user object.
   *
   * @param object $account
   *   A user object containing an array of roles to check.
   * @param array $script
   *   The script to check.
   *
   * @return bool
   *   A decision on if the current user is being tracked by scriptjunkie.
   */
  public function checkUserVisibility($account, $script) {
    $enabled = FALSE;

    // Is current user a member of a role that should be tracked?
    if ($this->checkRolesVisibility($account, $script)) {
      $enabled = TRUE;
    }

    return $enabled;
  }

  /**
   * Check roles script visibility.
   *
   * Based on visibility setting this function returns TRUE if scriptjunkie code
   * should be added for the current role and otherwise FALSE.
   *
   * @param object $account
   *   A user object containing an array of roles to check.
   * @param array $script
   *   The script to check.
   *
   * @return bool
   *   TRUE if the Script should be added for the current role. FALSE if not.
   */
  public function checkRolesVisibility($account, $script) {
    $enabled = TRUE;
    $roles = $script['roles']['visibility'];
    // One or more roles are selected for tracking.
    foreach ($account->getRoles() as $rid) {
      // Is the current user a member of one role selected in admin settings?
      if (isset($roles[$rid]) && $rid === $roles[$rid]) {
        // Current user is a member of a role that is selected in admin
        // settings.
        $enabled = FALSE;
        break;
      }
    }

    return $enabled;
  }

}
