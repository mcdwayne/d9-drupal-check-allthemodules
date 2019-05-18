<?php
/**
 * @file
 * Contains \Drupal\crossdomain\Entity\Crossdomain.
 */

namespace Drupal\crossdomain\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\crossdomain\CrossdomainInterface;

/**
 * Defines the Crossdomain entity.
 *
 * @EntityType(
 *   id = "crossdomain",
 *   label = @Translation("Crossdomain"),
 *   module = "crossdomain",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "list" = "Drupal\crossdomain\Controller\CrossdomainListController",
 *     "form" = {
 *       "add" = "Drupal\crossdomain\Controller\CrossdomainFormController",
 *       "edit" = "Drupal\crossdomain\Controller\CrossdomainFormController",
 *       "delete" = "Drupal\crossdomain\Form\CrossdomainDeleteForm"
 *     }
 *   },
 *   config_prefix = "crossdomain",
 *   admin_permission = "administer crossdomain",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "admin/config/media/crossdomain/{crossdomain}"
 *   }
 * )
 */
class Crossdomain extends ConfigEntityBase implements CrossdomainInterface {

/**
* The crossdomain ID.
*
* @var string
*/
  public $id;

  /**
   * The crossdomain UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The crossdomain label.
   *
   * @var string
   */
  public $label;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.
}
