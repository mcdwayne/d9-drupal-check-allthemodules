<?php

namespace Drupal\visualn_dataset\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the VisualN Data Set type entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_dataset_type",
 *   label = @Translation("VisualN Data Set type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_dataset\VisualNDataSetTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn_dataset\Form\VisualNDataSetTypeForm",
 *       "edit" = "Drupal\visualn_dataset\Form\VisualNDataSetTypeForm",
 *       "delete" = "Drupal\visualn_dataset\Form\VisualNDataSetTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_dataset\VisualNDataSetTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_dataset_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "visualn_dataset",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "resource_provider_field" = "resource_provider_field"
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/dataset-types/manage/{visualn_dataset_type}",
 *     "add-form" = "/admin/visualn/dataset-types/add",
 *     "edit-form" = "/admin/visualn/dataset-types/manage/{visualn_dataset_type}/edit",
 *     "delete-form" = "/admin/visualn/dataset-types/manage/{visualn_dataset_type}/delete",
 *     "collection" = "/admin/visualn/dataset-types"
 *   }
 * )
 */
class VisualNDataSetType extends ConfigEntityBundleBase implements VisualNDataSetTypeInterface {

  /**
   * The VisualN Data Set type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Data Set type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN Resource Provider field ID.
   *
   * @var string
   */
  protected $resource_provider_field;

  /**
   * {@inheritdoc}
   */
  public function getResourceProviderField() {
    return $this->resource_provider_field;
  }

}
