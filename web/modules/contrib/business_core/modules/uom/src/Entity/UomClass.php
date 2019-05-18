<?php

namespace Drupal\uom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uom\UomClassInterface;

/**
 * Defines the configured uom_class entity.
 *
 * @ConfigEntityType(
 *   id = "uom_class",
 *   label = @Translation("Uom class"),
 *   handlers = {
 *     "access" = "Drupal\uom\UomAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\uom\UomClassForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\uom\UomClassListBuilder",
 *   },
 *   admin_permission = "administer uom",
 *   config_prefix = "class",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/uom/class/add",
 *     "edit-form" = "/admin/uom/class/{uom_class}/edit",
 *     "delete-form" = "/admin/uom/class/{uom_class}/delete",
 *     "collection" = "/admin/uom/class",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "base_uom",
 *     "description",
 *   }
 * )
 */
class UomClass extends ConfigEntityBase implements UomClassInterface {

  /**
   * The name (plugin ID) of the uom_class.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the uom_class.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this UOM class.
   *
   * @var string
   */
  protected $description;

  /**
   * The base uom.
   *
   * @var string
   */
  protected $base_uom;

  /**
   * {@inheritdoc}
   */
  public function getBaseUom() {
    return $this->base_uom;
  }

}
