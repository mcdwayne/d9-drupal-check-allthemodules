<?php

namespace Drupal\profile_enforcer\EventSubscriber;

use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class ProfileEnforceRedirectSubscriber.
 */
class ProfileEnforceRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function checkIfFilled(GetResponseEvent $event) {
    $current_route = \Drupal::routeMatch()->getRouteName();
    $current_user = \Drupal::currentUser();
    $list_of_all_blocks[] = $current_route;
    $all_blocks = \Drupal::service('block.repository')->getVisibleBlocksPerRegion();
    foreach ($all_blocks as $region => $blocks) {
      foreach ($blocks as $block) {
        $list_of_all_blocks[] = $block->get('plugin');
      }
    }
    $uid = $current_user->id();
    $roles = $current_user->getRoles();
    $enforced_roles = \Drupal::configFactory()->getEditable('profile_enforcer.settings')->get('profile_enforce_roles');
    $roles_selected = explode(';', $enforced_roles);
    $redirect = TRUE;
    $profile_type = '';
    if ($uid && !empty(array_intersect($roles, $roles_selected)) && in_array('profile_enforcer_block', $list_of_all_blocks)) {
      $profile_type = \Drupal::configFactory()->getEditable('profile_enforcer.settings')
        ->get('profile_types');
      $profile_fields = explode(';', \Drupal::configFactory()->getEditable('profile_enforcer.settings')
        ->get('profile_enforce_fields'));
      if (!empty($profile_fields)) {
        if ($profile_type == 'user') {
          $profile_type = '';
          $user = User::load($uid);
          foreach ($profile_fields as $profile_field) {
            if (empty($user->get($profile_field)->getValue())) {
              $redirect = FALSE;
            }
          }
        }
        else {
          $user_profiles = \Drupal::entityTypeManager()->getStorage('profile')->loadByProperties(['uid' => $uid]);
          foreach ($profile_fields as $profile_field) {
            foreach ($user_profiles as $user_profile) {
              if ($user_profile->get($profile_field)->value == '') {
                $redirect = FALSE;
              }
            }
          }
        }
      }
    }
    if (!$redirect) {
      $request = $event->getRequest();
      $user_routes = array('user.login', 'user.logout', 'entity.user.canonical');
      if (\Drupal::configFactory()->getEditable('profile_enforcer.settings')->get('profile_types') == 'user' && !in_array($current_route, $user_routes)) {
        drupal_set_message(t('Please fill the profile fields.'), 'warning');
        $event->setResponse(new RedirectResponse($request->getBasePath() . '/user/' . $current_user->id() . '/edit'));
      }
      elseif (\Drupal::configFactory()->getEditable('profile_enforcer.settings')->get('profile_types') != 'user' && $current_route != 'user.logout') {
        drupal_set_message(t('Please fill the profile fields.'), 'warning');
        $event->setResponse(new RedirectResponse($request->getBasePath() . '/user/' . $current_user->id() . '/' . $profile_type));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkIfFilled'];
    return $events;
  }

}
