<?php

namespace Drupal\domain_menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\system\MenuAccessControlHandler;
use Drupal\system\MenuInterface;

/**
 * Replacement class for menu access control handler, Adds checking for domain-menu
 * assignment to the mix before deciding whether a menu is accessible.
 */
class DomainMenuAccessControlHandler extends MenuAccessControlHandler {

  /**
   * @inheritdoc
   *
   * @param EntityInterface $menu
   *    The menu entity to check access for.
   */
  public function checkAccess(EntityInterface $menu, $operation, AccountInterface $account) {

    if ($account->hasPermission('administer menu')) {
      // Users with the system wide "administer menu" permissions can do anything.
      return parent::checkAccess($menu, $operation, $account);
    }

    if ($account->hasPermission('administer domain menus')) {
      if ($operation == 'delete' && !$menu->isLocked() || $operation !== 'delete') {
        $allowed = FALSE;
        // @todo: rewrite this part to use DomainAccessManager::checkEntityAccess
        // (this is not possible now because DomainAccessManager doesn't deal with
        // config items)
        $menu_domains = $menu->getThirdPartySetting("domain_menu", "domains", []);
        $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
        if (!empty($this->getDomainAccessManager()->getAllValue($user)) && !empty($menu_domains)) {
          $allowed = TRUE;
        }
        else {
          $user_domains = $this->getDomainAccessManager()->getAccessValues($user);
          foreach ($menu_domains as $domain_key => $menu_has_domain) {
            if (isset($user_domains[$domain_key]) && $menu_has_domain) {
              $allowed = TRUE;
              break;
            }
          }
        }
        return AccessResult::allowedIf($allowed);
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * @return \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected function getDomainAccessManager() {
    return \Drupal::service('domain_access.manager');
  }

}
