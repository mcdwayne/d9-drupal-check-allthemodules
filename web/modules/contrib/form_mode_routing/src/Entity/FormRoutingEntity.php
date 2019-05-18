<?php

namespace Drupal\form_mode_routing\Entity;


use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Form routing entity entity.
 *
 * @ConfigEntityType(
 *   id = "form_routing_entity",
 *   label = @Translation("Form routing entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\form_mode_routing\FormRoutingEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\form_mode_routing\Form\FormRoutingEntityForm",
 *       "edit" = "Drupal\form_mode_routing\Form\FormRoutingEntityForm",
 *       "delete" = "Drupal\form_mode_routing\Form\FormRoutingEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\form_mode_routing\FormRoutingEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "form_routing_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/form_routing_entity/{form_routing_entity}",
 *     "add-form" = "/admin/structure/form_routing_entity/add",
 *     "edit-form" = "/admin/structure/form_routing_entity/{form_routing_entity}/edit",
 *     "delete-form" = "/admin/structure/form_routing_entity/{form_routing_entity}/delete",
 *     "collection" = "/admin/structure/form_routing_entity"
 *   }
 * )
 */
class FormRoutingEntity extends ConfigEntityBase implements FormRoutingEntityInterface {

  /**
   * The Form routing entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Form routing entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Form routing path.
   *
   * @var string
   */
  public $path;

  /**
   * The Form routing roles.
   *
   * @var string
   */
  public $access;


  public function getAccess() {
    return json_decode($this->access, TRUE);
  }

  public function setAccess(array $array) {
    if (count($array) != 0) {
      $this->access = json_encode($array);
    }
    return $this;
  }


}
