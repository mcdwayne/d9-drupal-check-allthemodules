<?php

namespace Drupal\task_note\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Task Note type entity.
 *
 * @ConfigEntityType(
 *   id = "task_note_type",
 *   label = @Translation("Task Note type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\task_note\TaskNoteTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\task_note\Form\TaskNoteTypeForm",
 *       "edit" = "Drupal\task_note\Form\TaskNoteTypeForm",
 *       "delete" = "Drupal\task_note\Form\TaskNoteTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\task_note\TaskNoteTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "task_note_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "task_note",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/task_note_type/{task_note_type}",
 *     "add-form" = "/admin/structure/task_note_type/add",
 *     "edit-form" = "/admin/structure/task_note_type/{task_note_type}/edit",
 *     "delete-form" = "/admin/structure/task_note_type/{task_note_type}/delete",
 *     "collection" = "/admin/structure/task_note_type"
 *   }
 * )
 */
class TaskNoteType extends ConfigEntityBundleBase implements TaskNoteTypeInterface {

  /**
   * The Task Note type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Task Note type label.
   *
   * @var string
   */
  protected $label;

}
