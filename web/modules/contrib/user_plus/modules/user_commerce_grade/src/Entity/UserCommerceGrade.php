<?php

namespace Drupal\user_commerce_grade\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the User commerce grade entity.
 *
 * @ConfigEntityType(
 *   id = "user_commerce_grade",
 *   label = @Translation("User grade"),
 *   label_collection = @Translation("User grade"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_commerce_grade\UserCommerceGradeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_commerce_grade\Form\UserCommerceGradeForm",
 *       "edit" = "Drupal\user_commerce_grade\Form\UserCommerceGradeForm",
 *       "delete" = "Drupal\user_commerce_grade\Form\UserCommerceGradeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_commerce_grade\UserCommerceGradeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "grade",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/people/user_commerce_grade/{user_commerce_grade}",
 *     "add-form" = "/admin/people/user_commerce_grade/add",
 *     "edit-form" = "/admin/people/user_commerce_grade/{user_commerce_grade}/edit",
 *     "delete-form" = "/admin/people/user_commerce_grade/{user_commerce_grade}/delete",
 *     "collection" = "/admin/people/user_commerce_grade"
 *   }
 * )
 */
class UserCommerceGrade extends ConfigEntityBase implements UserCommerceGradeInterface {

  /**
   * The User commerce grade ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User commerce grade label.
   *
   * @var string
   */
  protected $label;

}
