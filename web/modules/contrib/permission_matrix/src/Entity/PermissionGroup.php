<?php

namespace Drupal\permission_matrix\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Permission group entity.
 *
 * @ConfigEntityType(
 *   id = "permission_group",
 *   label = @Translation("Permission group"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\permission_matrix\PermissionGroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\permission_matrix\Form\PermissionGroupForm",
 *       "edit" = "Drupal\permission_matrix\Form\PermissionGroupForm",
 *       "delete" = "Drupal\permission_matrix\Form\PermissionGroupDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\permission_matrix\PermissionGroupHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "permission_group",
 *   admin_permission = "manage permission groups",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "permissions" = "permissions",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/permission_group/{permission_group}",
 *     "add-form" = "/admin/config/permission_group/add",
 *     "edit-form" = "/admin/config/permission_group/{permission_group}/edit",
 *     "delete-form" = "/admin/config/permission_group/{permission_group}/delete",
 *     "collection" = "/admin/config/permission_group"
 *   }
 * )
 */
class PermissionGroup extends ConfigEntityBase implements PermissionGroupInterface {

  /**
   * The Permission group ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Permission group label.
   *
   * @var string
   */
  protected $label;

  protected $permissions;

}
