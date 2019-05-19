<?php

namespace Drupal\task\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Task Closure Reason entity.
 *
 * @ConfigEntityType(
 *   id = "task_closure_reason",
 *   label = @Translation("Task Closure Reason"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\task\TaskClosureReasonListBuilder",
 *     "form" = {
 *       "add" = "Drupal\task\Form\TaskClosureReasonForm",
 *       "edit" = "Drupal\task\Form\TaskClosureReasonForm",
 *       "delete" = "Drupal\task\Form\TaskClosureReasonDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\task\TaskClosureReasonHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "task_closure_reason",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/task/closure_reasons/task_closure_reason/{task_closure_reason}",
 *     "add-form" = "/admin/structure/task/closure_reasons/task_closure_reason/add",
 *     "edit-form" = "/admin/structure/task/closure_reasons/task_closure_reason/{task_closure_reason}/edit",
 *     "delete-form" = "/admin/structure/task/closure_reasons/task_closure_reason/{task_closure_reason}/delete",
 *     "collection" = "/admin/structure/task/closure_reasons/task_closure_reason"
 *   }
 * )
 */
class TaskClosureReason extends ConfigEntityBase implements TaskClosureReasonInterface {

  /**
   * The Task Closure Reason ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Task Closure Reason label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether the Closure Reason is locked for editing.
   *
   * @var boolean
   */
  protected $locked;

  /**
   * The Task type description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return !empty($this->description) ? $this->description : '';
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !empty($this->locked) ? $this->locked : FALSE;
  }

}
