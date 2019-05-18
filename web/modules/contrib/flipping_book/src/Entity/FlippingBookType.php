<?php

namespace Drupal\flipping_book\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Flipping Book type entity.
 *
 * @ConfigEntityType(
 *   id = "flipping_book_type",
 *   label = @Translation("Flipping Book type"),
 *   handlers = {
 *     "list_builder" = "Drupal\flipping_book\FlippingBookTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\flipping_book\Form\FlippingBookTypeForm",
 *       "edit" = "Drupal\flipping_book\Form\FlippingBookTypeForm",
 *       "delete" = "Drupal\flipping_book\Form\FlippingBookTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\flipping_book\FlippingBookTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "flipping_book_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "flipping_book",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/flipping-book/{flipping_book_type}",
 *     "add-form" = "/admin/structure/flipping-book/add",
 *     "edit-form" = "/admin/structure/flipping-book/{flipping_book_type}/edit",
 *     "delete-form" = "/admin/structure/flipping-book/{flipping_book_type}/delete",
 *     "collection" = "/admin/structure/flipping-book"
 *   }
 * )
 */
class FlippingBookType extends ConfigEntityBundleBase implements FlippingBookTypeInterface {

  /**
   * The Flipping Book type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Flipping Book type label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getPermissionName() {
    if ($this->id()) {
      return 'access ' . $this->id() . ' flipping book';
    }
    return FALSE;
  }

}
