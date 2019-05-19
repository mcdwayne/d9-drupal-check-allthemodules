<?php

namespace Drupal\system_tags\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class SystemTag.
 *
 * @package Drupal\system_tags\Entity
 *
 * @ConfigEntityType(
 *   id = "system_tag",
 *   label = @Translation("System Tag"),
 *   handlers = {
 *     "list_builder" = "Drupal\system_tags\SystemTagListBuilder",
 *     "form" = {
 *       "add" = "Drupal\system_tags\Form\SystemTagForm",
 *       "edit" = "Drupal\system_tags\Form\SystemTagForm",
 *       "delete" = "Drupal\system_tags\Form\SystemTagDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\system_tags\SystemTagHtmlRouteProvider"
 *     },
 *   },
 *   config_prefix = "system_tag",
 *   admin_permission = "administer system tags",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/system_tags/add",
 *     "edit-form" = "/admin/structure/system_tags/{system_tag}",
 *     "delete-form" = "/admin/structure/system_tags/{system_tag}/delete",
 *     "collection" = "/admin/structure/system_tags"
 *   }
 * )
 */
class SystemTag extends ConfigEntityBase implements SystemTagInterface {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

}
