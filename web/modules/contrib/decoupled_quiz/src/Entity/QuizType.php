<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Quiz type entity.
 *
 * @ConfigEntityType(
 *   id = "quiz_type",
 *   label = @Translation("Quiz type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\decoupled_quiz\QuizTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\decoupled_quiz\Form\QuizTypeForm",
 *       "edit" = "Drupal\decoupled_quiz\Form\QuizTypeForm",
 *       "delete" = "Drupal\decoupled_quiz\Form\QuizTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\decoupled_quiz\QuizTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "quiz_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "quiz",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/quiz_type/{quiz_type}",
 *     "add-form" = "/admin/structure/quiz_type/add",
 *     "edit-form" = "/admin/structure/quiz_type/{quiz_type}/edit",
 *     "delete-form" = "/admin/structure/quiz_type/{quiz_type}/delete",
 *     "collection" = "/admin/structure/quiz_type"
 *   }
 * )
 */
class QuizType extends ConfigEntityBundleBase implements QuizTypeInterface {

  /**
   * The Quiz type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Quiz type label.
   *
   * @var string
   */
  protected $label;

}
