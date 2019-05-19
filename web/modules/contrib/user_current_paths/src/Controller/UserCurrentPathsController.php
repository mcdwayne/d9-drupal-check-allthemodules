<?php

namespace Drupal\user_current_paths\Controller;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class UserCurrentPathsController extends ControllerBase
{

  /**
   * Handles wildcard (user/current/*) redirects for the current user.
   * Replaces the second "current" parameter in the URL with the currently logged in user
   * and redirects to the target if the resulting path is valid. Ohterwise throws a NotFoundHttpException.
   * This is safe because the redirect is handled as if the user entered the URL manually with all security checks.
   *
   * @param string $wildcardaction
   * @param Request $request
   * @return void
   */
  public function wildcardActionRedirect($wildcardaction = 'view', Request $request)
  {
    $currentUserId = (int)\Drupal::currentUser()->id();
    $path = '/user/' . $currentUserId;
    if ($wildcardaction != 'view') {
      // /view doesn't exist for user entities
      $path .= '/' . $wildcardaction;
    }
    $url = \Drupal::service('path.validator')->getUrlIfValid($path);
    if ($url !== false) {
      // Valid internal path:
      return $this->redirect($url);
    } else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Handles redirects to the user edit page (user/edit) for the currently logged in user.
   *
   * @param Request $request
   * @return void
   */
  public function editRedirect(Request $request)
  {
    $route_name = 'entity.user.edit_form';
    $route_parameters = [
      'user' => \Drupal::currentUser()->id(),
    ];
    return $this->redirect($route_name, $route_parameters);
  }
}
