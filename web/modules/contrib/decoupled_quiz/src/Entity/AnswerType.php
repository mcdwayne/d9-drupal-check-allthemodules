<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Answer type entity.
 *
 * @ConfigEntityType(
 *   id = "answer_type",
 *   label = @Translation("Answer type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\decoupled_quiz\AnswerTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\decoupled_quiz\Form\AnswerTypeForm",
 *       "edit" = "Drupal\decoupled_quiz\Form\AnswerTypeForm",
 *       "delete" = "Drupal\decoupled_quiz\Form\AnswerTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\decoupled_quiz\AnswerTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "answer_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "answer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/answer_type/{answer_type}",
 *     "add-form" = "/admin/structure/answer_type/add",
 *     "edit-form" = "/admin/structure/answer_type/{answer_type}/edit",
 *     "delete-form" = "/admin/structure/answer_type/{answer_type}/delete",
 *     "collection" = "/admin/structure/answer_type"
 *   }
 * )
 */
class AnswerType extends ConfigEntityBundleBase implements AnswerTypeInterface {

  /**
   * The Answer type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Answer type label.
   *
   * @var string
   */
  protected $label;

}
