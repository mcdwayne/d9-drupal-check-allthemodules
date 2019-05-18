<?php

namespace Drupal\uom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uom\UomInterface;

/**
 * Defines the configured uom entity.
 *
 * @ConfigEntityType(
 *   id = "uom",
 *   label = @Translation("Unit of Measure"),
 *   handlers = {
 *     "access" = "Drupal\uom\UomAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\uom\UomForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\uom\UomListBuilder",
 *   },
 *   admin_permission = "administer uoms",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/uom/add",
 *     "edit-form" = "/admin/uom/{uom}/edit",
 *     "delete-form" = "/admin/uom/{uom}/delete",
 *     "collection" = "/admin/uom",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "class",
 *     "conversion_factor",
 *     "description",
 *   }
 * )
 */
class Uom extends ConfigEntityBase implements UomInterface {

  /**
   * The name of the uom.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the uom.
   *
   * @var string
   */
  protected $label;

  /**
   * The uom class.
   *
   * @var string
   */
  protected $class;

  /**
   * The conversion factor by which the UOM is equivalent to the base UOM
   * established for the UOM class.
   *
   * @var float
   */
  protected $conversion_factor;

  /**
   * A brief description of this UOM.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('uom.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversionFactor() {
    return $this->conversion_factor;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

}
