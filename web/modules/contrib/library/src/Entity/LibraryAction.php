<?php

namespace Drupal\library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\library\LibraryActionInterface;

/**
 * Defines the Library action entity.
 *
 * @ConfigEntityType(
 *   id = "library_action",
 *   label = @Translation("Library actions"),
 *   handlers = {
 *     "list_builder" = "Drupal\library\LibraryActionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\library\Form\LibraryActionForm",
 *       "edit" = "Drupal\library\Form\LibraryActionForm",
 *       "delete" = "Drupal\library\Form\LibraryActionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\library\LibraryActionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "library_action",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/library_action/{library_action}",
 *     "add-form" = "/admin/structure/library_action/add",
 *     "edit-form" = "/admin/structure/library_action/{library_action}/edit",
 *     "delete-form" = "/admin/structure/library_action/{library_action}/delete",
 *     "collection" = "/admin/structure/library_action"
 *   }
 * )
 */
class LibraryAction extends ConfigEntityBase implements LibraryActionInterface {
  /**
   * The Library action ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Library action label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Library action itself.
   *
   * @var int
   */
  protected $action;

  /**
   * Returns the action.
   *
   * TODO: Verify that this call is internally consistent (instead of $id).
   *
   * @return int
   *   The action by legacy ID.
   */
  public function action() {
    return $this->action;
  }

}
