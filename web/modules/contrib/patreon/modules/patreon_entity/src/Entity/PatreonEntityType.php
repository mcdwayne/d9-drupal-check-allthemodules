<?php

namespace Drupal\patreon_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Patreon entity type entity.
 *
 * @ConfigEntityType(
 *   id = "patreon_entity_type",
 *   label = @Translation("Patreon entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\patreon_entity\PatreonEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\patreon_entity\Form\PatreonEntityTypeForm",
 *       "edit" = "Drupal\patreon_entity\Form\PatreonEntityTypeForm",
 *       "delete" = "Drupal\patreon_entity\Form\PatreonEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\patreon_entity\PatreonEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "patreon_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "patreon_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/patreon_entity_type/{patreon_entity_type}",
 *     "add-form" = "/admin/structure/patreon_entity_type/add",
 *     "edit-form" = "/admin/structure/patreon_entity_type/{patreon_entity_type}/edit",
 *     "delete-form" = "/admin/structure/patreon_entity_type/{patreon_entity_type}/delete",
 *     "collection" = "/admin/structure/patreon_entity_type"
 *   }
 * )
 */
class PatreonEntityType extends ConfigEntityBundleBase implements PatreonEntityTypeInterface {

  /**
   * The Patreon entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Patreon entity type label.
   *
   * @var string
   */
  protected $label;

}
