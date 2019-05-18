<?php

namespace Drupal\config_role_split\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Role Split entity.
 *
 * @ConfigEntityType(
 *   id = "role_split",
 *   label = @Translation("Role Split"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\config_role_split\RoleSplitEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\config_role_split\Form\RoleSplitEntityForm",
 *       "edit" = "Drupal\config_role_split\Form\RoleSplitEntityForm",
 *       "delete" = "Drupal\config_role_split\Form\RoleSplitEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\config_role_split\RoleSplitEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "role_split",
 *   admin_permission = "administer config role split",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/configuration/config-role-split/{role_split}",
 *     "add-form" = "/admin/config/development/configuration/config-role-split/add",
 *     "edit-form" = "/admin/config/development/configuration/config-role-split/{role_split}/edit",
 *     "delete-form" = "/admin/config/development/configuration/config-role-split/{role_split}/delete",
 *     "collection" = "/admin/config/development/configuration/config-role-split"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "status",
 *     "mode",
 *     "roles",
 *   }
 * )
 */
class RoleSplitEntity extends ConfigEntityBase implements RoleSplitEntityInterface {

  /**
   * The Role Split ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Role Split label.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of the configuration when spliting several folders.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The status, whether to be used by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The mode of the filter.
   *
   * @var string
   */
  protected $mode = 'split';

  /**
   * The roles to filter.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    parent::invalidateTagsOnDelete($entity_type, $entities);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

}
