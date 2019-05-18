<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Result type entity.
 *
 * @ConfigEntityType(
 *   id = "result_type",
 *   label = @Translation("Result type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\decoupled_quiz\ResultTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\decoupled_quiz\Form\ResultTypeForm",
 *       "edit" = "Drupal\decoupled_quiz\Form\ResultTypeForm",
 *       "delete" = "Drupal\decoupled_quiz\Form\ResultTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\decoupled_quiz\ResultTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "result_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "result",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/result_type/{result_type}",
 *     "add-form" = "/admin/structure/result_type/add",
 *     "edit-form" = "/admin/structure/result_type/{result_type}/edit",
 *     "delete-form" = "/admin/structure/result_type/{result_type}/delete",
 *     "collection" = "/admin/structure/result_type"
 *   }
 * )
 */
class ResultType extends ConfigEntityBundleBase implements ResultTypeInterface {

  /**
   * The Result type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Result type label.
   *
   * @var string
   */
  protected $label;

}
