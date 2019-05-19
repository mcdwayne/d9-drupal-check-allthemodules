<?php

namespace Drupal\simple_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Simple content type entity.
 *
 * @ConfigEntityType(
 *   id = "simple_content_type",
 *   label = @Translation("Simple content type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\simple_content\SimpleContentTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_content\Form\SimpleContentTypeForm",
 *       "edit" = "Drupal\simple_content\Form\SimpleContentTypeForm",
 *       "delete" = "Drupal\simple_content\Form\SimpleContentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "simple_content_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "simple_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/simple_content_type/{simple_content_type}",
 *     "add-form" = "/admin/structure/simple_content_type/add",
 *     "edit-form" = "/admin/structure/simple_content_type/{simple_content_type}/edit",
 *     "delete-form" = "/admin/structure/simple_content_type/{simple_content_type}/delete",
 *     "collection" = "/admin/structure/simple_content_type"
 *   }
 * )
 */
class SimpleContentType extends ConfigEntityBundleBase implements SimpleContentTypeInterface {

  /**
   * The Simple content type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Simple content type label.
   *
   * @var string
   */
  protected $label;

}
