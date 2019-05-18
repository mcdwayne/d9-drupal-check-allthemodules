<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\Entity\HierarchicalConfigurationType.
 */

namespace Drupal\hierarchical_config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\hierarchical_config\HierarchicalConfigurationTypeInterface;

/**
 * Defines the Hierarchical configuration type entity.
 *
 * @ConfigEntityType(
 *   id = "hierarchical_configuration_type",
 *   label = @Translation("Hierarchical configuration bundle"),
 *   handlers = {
 *     "list_builder" = "Drupal\hierarchical_config\HierarchicalConfigurationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hierarchical_config\Form\HierarchicalConfigurationTypeForm",
 *       "edit" = "Drupal\hierarchical_config\Form\HierarchicalConfigurationTypeForm",
 *       "delete" = "Drupal\hierarchical_config\Form\HierarchicalConfigurationTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\hierarchical_config\HierarchicalConfigurationTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "hierarchical_configuration_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "hierarchical_configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/hierarchical_configuration_type/{hierarchical_configuration_type}",
 *     "add-form" = "/admin/structure/hierarchical_configuration_type/add",
 *     "edit-form" = "/admin/structure/hierarchical_configuration_type/{hierarchical_configuration_type}/edit",
 *     "delete-form" = "/admin/structure/hierarchical_configuration_type/{hierarchical_configuration_type}/delete",
 *     "collection" = "/admin/structure/hierarchical_configuration_type"
 *   }
 * )
 */
class HierarchicalConfigurationType extends ConfigEntityBundleBase implements HierarchicalConfigurationTypeInterface {
  /**
   * The Hierarchical configuration type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Hierarchical configuration type label.
   *
   * @var string
   */
  protected $label;

}
