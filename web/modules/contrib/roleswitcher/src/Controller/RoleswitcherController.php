<?php

namespace Drupal\roleswitcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AccountProxyInterface;

class RoleswitcherController extends ControllerBase {

  public function switchRole($rid, Request $request) {
    // Redirect to the front page if destination does not exist.
    $destination = $request->get('destination');
    $url = empty($destination) ? '/' : $destination;

    if ($rid == 'reset') {
      unset($_SESSION['roleswitcher_roles']);
      return new RedirectResponse($url);
    }

    /** @var AccountProxyInterface */
    $sessionUser = \Drupal::currentUser();

    /** @var UserInterface $user */
    $user = user_load($sessionUser->id());

    // Clear current roles.
    foreach ($user->getRoles() as $rolename) {
      if ($rolename != 'roleswitcher') {
        $user->removeRole($rolename);
      }
    }
    $_SESSION['roleswitcher_roles'] = array(0 => $rid);
    // Assign requested role.
    $user->addRole($rid);

    $sessionUser->setAccount($user);

    return new RedirectResponse($url);
  }
}