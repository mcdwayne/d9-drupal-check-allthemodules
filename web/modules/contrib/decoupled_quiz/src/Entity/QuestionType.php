<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Question type entity.
 *
 * @ConfigEntityType(
 *   id = "question_type",
 *   label = @Translation("Question type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\decoupled_quiz\QuestionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\decoupled_quiz\Form\QuestionTypeForm",
 *       "edit" = "Drupal\decoupled_quiz\Form\QuestionTypeForm",
 *       "delete" = "Drupal\decoupled_quiz\Form\QuestionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\decoupled_quiz\QuestionTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "question_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "question",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/question_type/{question_type}",
 *     "add-form" = "/admin/structure/question_type/add",
 *     "edit-form" = "/admin/structure/question_type/{question_type}/edit",
 *     "delete-form" = "/admin/structure/question_type/{question_type}/delete",
 *     "collection" = "/admin/structure/question_type"
 *   }
 * )
 */
class QuestionType extends ConfigEntityBundleBase implements QuestionTypeInterface {

  /**
   * The Question type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Question type label.
   *
   * @var string
   */
  protected $label;

}
