<?php

namespace Drupal\user_attendance\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the User attendance type entity.
 *
 * @ConfigEntityType(
 *   id = "user_attendance_type",
 *   label = @Translation("User attendance type"),
 *   handlers = {
 *     "list_builder" = "Drupal\user_attendance\UserAttendanceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_attendance\Form\UserAttendanceTypeForm",
 *       "edit" = "Drupal\user_attendance\Form\UserAttendanceTypeForm",
 *       "delete" = "Drupal\user_attendance\Form\UserAttendanceTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\user_attendance\UserAttendanceTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "user_attendance_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "user_attendance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/people/user_attendance_type/{user_attendance_type}",
 *     "add-form" = "/admin/people/user_attendance_type/add",
 *     "edit-form" = "/admin/people/user_attendance_type/{user_attendance_type}/edit",
 *     "delete-form" = "/admin/people/user_attendance_type/{user_attendance_type}/delete",
 *     "collection" = "/admin/people/user_attendance_type"
 *   }
 * )
 */
class UserAttendanceType extends ConfigEntityBundleBase implements UserAttendanceTypeInterface {

  /**
   * The User attendance type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User attendance type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The User attendance type duplicate protection range in seconds.
   *
   * @var string
   */
  protected $duplicate_protection;

}
