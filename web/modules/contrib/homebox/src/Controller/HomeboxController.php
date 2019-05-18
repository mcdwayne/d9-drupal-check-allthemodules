<?php

namespace Drupal\homebox\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\homebox\Entity\HomeboxInterface;

/**
 * Class HomeboxController.
 *
 * @package homebox\Controller
 */
class HomeboxController extends ControllerBase {

  /**
   * Check access to Homebox entity.
   *
   * @param \Drupal\homebox\Entity\HomeboxInterface $homebox
   *   Homebox entity.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Access result.
   */
  public function access(HomeboxInterface $homebox) {
    $user = $this->currentUser();
    if ($user->hasPermission('administer homebox')) {
      return AccessResult::allowed();
    }
    if ($homebox->getOptions()['status']) {
      $user_roles = $user->getRoles();
      $allowed_roles = array_values($homebox->getRoles());
      foreach ($user_roles as $delta => $role) {
        if (in_array($role, $allowed_roles)) {
          $access = AccessResult::allowed();
          return $access;
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Provides the reference submission form.
   *
   * @param \Drupal\homebox\Entity\HomeboxInterface $homebox
   *   The homebox.
   *
   * @return array
   *   A homebox layout submission form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add(HomeboxInterface $homebox) {
    $entity = $this->entityTypeManager()->getStorage('homebox_layout')->create([
      'type' => $homebox->id(),
    ]);

    return $this->entityFormBuilder()->getForm($entity);
  }

}
