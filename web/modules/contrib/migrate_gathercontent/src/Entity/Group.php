<?php
namespace Drupal\migrate_gathercontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\migrate_gathercontent\GroupInterface;

/**
 * Defines the Example entity.
 *
 * @ConfigEntityType(
 *   id = "gathercontent_group",
 *   label = @Translation("GatherContent Group"),
 *   handlers = {
 *     "list_builder" = "Drupal\migrate_gathercontent\Controller\GroupListBuilder",
 *     "form" = {
 *       "default" = "Drupal\migrate_gathercontent\Form\GroupForm",
 *       "edit" = "Drupal\migrate_gathercontent\Form\GroupForm",
 *       "delete" = "Drupal\migrate_gathercontent\Form\GroupDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *    "id" = "group_id",
 *    "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/services/gatherocntent/mappings/group/add",
 *     "edit-form" = "/admin/config/services/gatherocntent/mappings/group/{group_id}/edit",
 *     "delete-form" = "/admin/config/services/gatherocntent/mappings/group/{group_id}/delete"
 *   },
 *   config_export = {
 *     "group_id",
 *     "label"
 *   },
 * )
 */
class Group extends ConfigEntityBase implements GroupInterface {

  /**
   * The ID.
   *
   * @var string
   */
  protected $group_id;

  /**
   * The Label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->group_id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

}