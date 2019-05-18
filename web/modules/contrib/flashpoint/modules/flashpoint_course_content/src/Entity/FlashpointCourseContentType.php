<?php

namespace Drupal\flashpoint_course_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Flashpoint course content type entity.
 *
 * @ConfigEntityType(
 *   id = "flashpoint_course_content_type",
 *   label = @Translation("Flashpoint course content type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\flashpoint_course_content\FlashpointCourseContentTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentTypeForm",
 *       "edit" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentTypeForm",
 *       "delete" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\flashpoint_course_content\FlashpointCourseContentTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "flashpoint_course_content_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "flashpoint_course_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/flashpoint/flashpoint_course_content_type/{flashpoint_course_content_type}",
 *     "add-form" = "/admin/structure/flashpoint/flashpoint_course_content_type/add",
 *     "edit-form" = "/admin/structure/flashpoint/flashpoint_course_content_type/{flashpoint_course_content_type}/edit",
 *     "delete-form" = "/admin/structure/flashpoint/flashpoint_course_content_type/{flashpoint_course_content_type}/delete",
 *     "collection" = "/admin/structure/flashpoint/flashpoint_course_content_type"
 *   }
 * )
 */
class FlashpointCourseContentType extends ConfigEntityBundleBase implements FlashpointCourseContentTypeInterface {

  /**
   * The Flashpoint course content type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Flashpoint course content type label.
   *
   * @var string
   */
  protected $label;

}
