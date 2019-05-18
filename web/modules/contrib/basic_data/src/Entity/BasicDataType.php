<?php

namespace Drupal\basic_data\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Basic Data type entity.
 *
 * @ConfigEntityType(
 *   id = "basic_data_type",
 *   label = @Translation("Basic Data type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\basic_data\BasicDataTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\basic_data\Form\BasicDataTypeForm",
 *       "edit" = "Drupal\basic_data\Form\BasicDataTypeForm",
 *       "delete" = "Drupal\basic_data\Form\BasicDataTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\basic_data\BasicDataTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "basic_data_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "basic_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/basic_data_type/{basic_data_type}",
 *     "add-form" = "/admin/structure/basic_data_type/add",
 *     "edit-form" = "/admin/structure/basic_data_type/{basic_data_type}/edit",
 *     "delete-form" = "/admin/structure/basic_data_type/{basic_data_type}/delete",
 *     "collection" = "/admin/structure/basic_data_type"
 *   }
 * )
 */
class BasicDataType extends ConfigEntityBundleBase implements BasicDataTypeInterface {

  /**
   * The Basic Data type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Basic Data type label.
   *
   * @var string
   */
  protected $label;

}
