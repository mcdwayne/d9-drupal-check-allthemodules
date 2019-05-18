<?php

namespace Drupal\permanent_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Permanent Entity type entity.
 *
 * @ConfigEntityType(
 *   id = "permanent_entity_type",
 *   label = @Translation("Permanent Entity Type"),
 *   label_collection = @Translation("Permanent Entity Types"),
 *   label_singular = @Translation("permanent entity type"),
 *   label_plural = @Translation("permanent entity type"),
 *   label_count = @PluralTranslation(
 *     singular = "@count permanent entity type",
 *     plural = "@count permanent entity types",
 *   ),
 *   bundle_label = @Translation("Permanent Entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\permanent_entities\PermanentEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\permanent_entities\Form\PermanentEntityTypeForm",
 *       "edit" = "Drupal\permanent_entities\Form\PermanentEntityTypeForm",
 *       "delete" = "Drupal\permanent_entities\Form\PermanentEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\permanent_entities\PermanentEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "permanent_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "permanent_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/permanent_entity_types/{permanent_entity_type}",
 *     "add-form" = "/admin/structure/permanent_entity_types/add",
 *     "edit-form" = "/admin/structure/permanent_entity_types/{permanent_entity_type}/edit",
 *     "delete-form" = "/admin/structure/permanent_entity_types/{permanent_entity_type}/delete",
 *     "collection" = "/admin/structure/permanent_entity_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   }
 * )
 */
class PermanentEntityType extends ConfigEntityBundleBase implements PermanentEntityTypeInterface {

  /**
   * The Permanent Entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Permanent Entity type label.
   *
   * @var string
   */
  protected $label;

}
