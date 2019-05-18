<?php

namespace Drupal\pagedesigner\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Pagedesigner type entity.
 *
 * @ConfigEntityType(
 *   id = "pagedesigner_type",
 *   label = @Translation("Pagedesigner type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pagedesigner\ElementTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pagedesigner\Form\ElementTypeForm",
 *       "edit" = "Drupal\pagedesigner\Form\ElementTypeForm",
 *       "delete" = "Drupal\pagedesigner\Form\ElementTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\pagedesigner\ElementTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "pagedesigner_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "pagedesigner_element",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/pagedesigner_type/{pagedesigner_type}",
 *     "add-form" = "/admin/structure/pagedesigner_type/add",
 *     "edit-form" = "/admin/structure/pagedesigner_type/{pagedesigner_type}/edit",
 *     "delete-form" = "/admin/structure/pagedesigner_type/{pagedesigner_type}/delete",
 *     "collection" = "/admin/structure/pagedesigner_type"
 *   }
 * )
 */
class ElementType extends ConfigEntityBundleBase implements ElementTypeInterface {

  /**
   * The Pagedesigner type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Pagedesigner type label.
   *
   * @var string
   */
  protected $label;

}
