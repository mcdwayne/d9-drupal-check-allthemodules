<?php

namespace Drupal\sapi_data\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\sapi_data\SAPIDataTypeInterface;

/**
 * Defines the Statistics API Data entry type entity.
 *
 * @ConfigEntityType(
 *   id = "sapi_data_type",
 *   label = @Translation("SAPI Data entry type"),
 *   handlers = {
 *     "list_builder" = "Drupal\sapi_data\SAPIDataTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sapi_data\Form\SAPIDataTypeForm",
 *       "edit" = "Drupal\sapi_data\Form\SAPIDataTypeForm",
 *       "delete" = "Drupal\sapi_data\Form\SAPIDataTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\sapi_data\SAPIDataTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "sapi_data_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "sapi_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/sapi/types/sapi_data_type/{sapi_data_type}",
 *     "add-form" = "/admin/structure/sapi/types/sapi_data_type/add",
 *     "edit-form" = "/admin/structure/sapi/types/sapi_data_type/{sapi_data_type}/edit",
 *     "delete-form" = "/admin/structure/sapi/types/sapi_data_type/{sapi_data_type}/delete",
 *     "collection" = "/admin/structure/sapi/types/sapi_data_type"
 *   }
 * )
 */
class SAPIDataType extends ConfigEntityBundleBase implements SAPIDataTypeInterface {
  /**
   * The Statistics API Data entry type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Statistics API Data entry type label.
   *
   * @var string
   */
  protected $label;

}
