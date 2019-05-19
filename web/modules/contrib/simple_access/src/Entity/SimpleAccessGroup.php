<?php

namespace Drupal\simple_access\Entity;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\simple_access\SimpleAccessGroupInterface;

/**
 * Defines the SimpleAccessGroup configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "simple_access_group",
 *   label = @Translation("Access group"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\simple_access\Controller\SimpleAccessGroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_access\Form\SimpleAccessGroupAddForm",
 *       "edit" = "Drupal\simple_access\Form\SimpleAccessGroupEditForm",
 *       "delete" = "Drupal\simple_access\Form\SimpleAccessGroupDeleteForm",
 *     }
 *   },
 *   config_prefix = "group",
 *   admin_permission = "manage simple access",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/simple-access/groups/{simple_access_group}/edit",
 *     "delete-form" = "/admin/config/content/simple-access/groups/{simple_access_group}/delete"
 *   }
 * )
 */
class SimpleAccessGroup extends ConfigEntityBase implements SimpleAccessGroupInterface {

  /**
   * Group Id.
   *
   * @var string
   */
  public $id;

  /**
   * Group Label.
   *
   * @var sting
   */
  public $label;

  /**
   * Group roles.
   *
   * @var array
   */
  public $roles;

  /**
   * Group Weight.
   *
   * @var int
   */
  public $weight;

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($this->id() == 'owner' && $operation == 'delete') {
      return $return_as_object ? new AccessResultForbidden() : FALSE;
    }

    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * Checks to see if the user has permission to use this role.
   *
   * @param $type_id
   *   Node type which to check permissions.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Account to check the access.
   *
   * @return bool
   *   Indicates if the account is able to access this group.
   */
  public function canManageAccess($type_id, AccountProxyInterface $account = NULL) {
    if (\Drupal::config('simple_access.settings')->get('show_groups')) {
      return TRUE;
    }

    if (!$account) {
      /** @var \Drupal\Core\Session\AccountProxy $account */
      $account = \Drupal::currentUser();
    }

    if ($this->id() == 'owner') {
      return $account->hasPermission('assign owner permissions') || $account->hasPermission("assign owner permissions for $type_id");
    }

    $roles = $account->getRoles();
    if (array_intersect($roles, $this->roles)) {
      return $account->hasPermission('assign groups to nodes') || $account->hasPermission("assign groups to $type_id nodes");
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildGrant(AccountInterface $account, $op) {
    foreach (array_filter($this->roles) as $rid) {
      if ($this->id == 'owner' || in_array($rid, $account->getRoles())) {
        return ['simple_access_group:' . $this->id() => [$this->id == 'owner' ? $account->id() : '0']];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Remove all records relating to this access group.
    \Drupal::database()->delete('simple_access_node_group')
      ->condition('gid', $this->id())
      ->execute();

    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    if ($a->id() === 'owner') {
      return -1;
    }
    elseif ($b->id() == 'owner') {
      return 1;
    }

    return parent::sort($a, $b);
  }

}
