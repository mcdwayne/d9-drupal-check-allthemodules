<?php

namespace Drupal\entity_modifier\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Entity modifier type entity.
 *
 * @ConfigEntityType(
 *   id = "entity_modifier_type",
 *   label = @Translation("Entity modifier type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_modifier\EntityModifierTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_modifier\Form\EntityModifierTypeForm",
 *       "edit" = "Drupal\entity_modifier\Form\EntityModifierTypeForm",
 *       "delete" = "Drupal\entity_modifier\Form\EntityModifierTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_modifier\EntityModifierTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_modifier_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "entity_modifier",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity_modifier_type/{entity_modifier_type}",
 *     "add-form" = "/admin/structure/entity_modifier_type/add",
 *     "edit-form" = "/admin/structure/entity_modifier_type/{entity_modifier_type}/edit",
 *     "delete-form" = "/admin/structure/entity_modifier_type/{entity_modifier_type}/delete",
 *     "collection" = "/admin/structure/entity_modifier_type"
 *   }
 * )
 */
class EntityModifierType extends ConfigEntityBundleBase implements EntityModifierTypeInterface {

  /**
   * The Entity modifier type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity modifier type label.
   *
   * @var string
   */
  protected $label;

}
