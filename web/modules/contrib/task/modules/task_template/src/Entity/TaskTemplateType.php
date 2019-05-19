<?php

namespace Drupal\task_template\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Task Template type entity.
 *
 * @ConfigEntityType(
 *   id = "task_template_type",
 *   label = @Translation("Task Template type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\task_template\TaskTemplateTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\task_template\Form\TaskTemplateTypeForm",
 *       "edit" = "Drupal\task_template\Form\TaskTemplateTypeForm",
 *       "delete" = "Drupal\task_template\Form\TaskTemplateTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\task_template\TaskTemplateTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "task_template_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "task_template",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/task_template_type/{task_template_type}",
 *     "add-form" = "/admin/structure/task_template_type/add",
 *     "edit-form" = "/admin/structure/task_template_type/{task_template_type}/edit",
 *     "delete-form" = "/admin/structure/task_template_type/{task_template_type}/delete",
 *     "collection" = "/admin/structure/task_template_type"
 *   }
 * )
 */
class TaskTemplateType extends ConfigEntityBundleBase implements TaskTemplateTypeInterface {

  /**
   * The Task Template type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Task Template type label.
   *
   * @var string
   */
  protected $label;

}
