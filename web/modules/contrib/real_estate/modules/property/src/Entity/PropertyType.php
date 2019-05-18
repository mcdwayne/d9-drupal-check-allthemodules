<?php

namespace Drupal\real_estate_property\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Property type entity.
 *
 * @ConfigEntityType(
 *   id = "real_estate_property_type",
 *   label = @Translation("Property type"),
 *   handlers = {
 *     "list_builder" = "Drupal\real_estate_property\PropertyTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\real_estate_property\Form\PropertyTypeForm",
 *       "edit" = "Drupal\real_estate_property\Form\PropertyTypeForm",
 *       "delete" = "Drupal\real_estate_property\Form\PropertyTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\real_estate_property\PropertyTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "real_estate_property_type",
 *   admin_permission = "administer real estate property type",
 *   bundle_of = "real_estate_property",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/real-estate/config/property-type/{real_estate_property_type}",
 *     "add-form" = "/admin/real-estate/config/property-type/add",
 *     "edit-form" = "/admin/real-estate/config/property-type/{real_estate_property_type}/edit",
 *     "delete-form" = "/admin/real-estate/config/property-type/{real_estate_property_type}/delete",
 *     "collection" = "/admin/real-estate/config/property-types"
 *   }
 * )
 */
class PropertyType extends ConfigEntityBundleBase implements PropertyTypeInterface {

  /**
   * The Property type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Property type label.
   *
   * @var string
   */
  protected $label;

}
