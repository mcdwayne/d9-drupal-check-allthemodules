<?php

namespace Drupal\phones_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Phones contact type entity.
 *
 * @ConfigEntityType(
 *   id = "phones_contact_type",
 *   label = @Translation("Phones contact type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\phones_contact\PhonesContactTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\phones_contact\Form\PhonesContactTypeForm",
 *       "edit" = "Drupal\phones_contact\Form\PhonesContactTypeForm",
 *       "delete" = "Drupal\phones_contact\Form\PhonesContactTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\phones_contact\PhonesContactTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "phones_contact_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "phones_contact",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/phones/phones_contact_type/{phones_contact_type}",
 *     "add-form" = "/admin/structure/phones/phones_contact_type/add",
 *     "edit-form" = "/admin/structure/phones/phones_contact_type/{phones_contact_type}/edit",
 *     "delete-form" = "/admin/structure/phones/phones_contact_type/{phones_contact_type}/delete",
 *     "collection" = "/admin/structure/phones/phones_contact_type"
 *   }
 * )
 */
class PhonesContactType extends ConfigEntityBundleBase implements PhonesContactTypeInterface {

  /**
   * The Phones contact type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Phones contact type label.
   *
   * @var string
   */
  protected $label;

}
