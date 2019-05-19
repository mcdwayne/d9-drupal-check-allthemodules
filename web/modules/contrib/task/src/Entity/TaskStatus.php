<?php

namespace Drupal\task\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Task Status entity.
 *
 * @ConfigEntityType(
 *   id = "task_status",
 *   label = @Translation("Task Status"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\task\TaskStatusListBuilder",
 *     "form" = {
 *       "add" = "Drupal\task\Form\TaskStatusForm",
 *       "edit" = "Drupal\task\Form\TaskStatusForm",
 *       "delete" = "Drupal\task\Form\TaskStatusDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\task\TaskStatusHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "task_status",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/task/statuses/task_status/{task_status}",
 *     "add-form" = "/admin/structure/task/statuses/task_status/add",
 *     "edit-form" = "/admin/structure/task/statuses/task_status/{task_status}/edit",
 *     "delete-form" = "/admin/structure/task/statuses/task_status/{task_status}/delete",
 *     "collection" = "/admin/structure/task/statuses/task_status"
 *   }
 * )
 */
class TaskStatus extends ConfigEntityBase implements TaskStatusInterface {

  /**
   * The Task Status ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Task Status label.
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
