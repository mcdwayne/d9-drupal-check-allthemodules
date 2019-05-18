<?php

namespace Drupal\ovh\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Définit le type d'entité vehicule_type.
 *
 * @ingroup vehicule
 *
 * @ConfigEntityType(
 *   id = "ovh_api_key",
 *   label = @Translation("OVH API key"),
 *   handlers = {
 *     "list_builder" = "Drupal\ovh\Entity\Controller\OvhKeyListBuilder",
 *     "view_builder" = "Drupal\ovh\Entity\Controller\OvhKeyViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\ovh\Entity\Form\OvhKeyForm",
 *       "edit" = "Drupal\ovh\Entity\Form\OvhKeyForm",
 *       "delete" = "Drupal\ovh\Entity\Form\OvhKeyDeleteForm",
 *     },
 *     "access" = "Drupal\ovh\OvhAccessControlHandler",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/ovh/ovh_api_key/{ovh_api_key}",
 *     "edit-form" = "/admin/config/services/ovh/ovh_api_key/{ovh_api_key}/edit",
 *     "delete-form" = "/admin/config/services/ovh/ovh_api_key/{ovh_api_key}/delete",
 *     "collection" = "/admin/config/services/ovh/keys"
 *   },
 * )
 */
class OvhKey extends ConfigEntityBase {

  /**
   * L'identifiant de l'entité.
   *
   * @var int
   */
  public $id;

  /**
   * L'identifiant unique (sur le site).
   *
   * @var string
   */
  public $uuid;

  /**
   * Le label.
   *
   * @var string
   */
  public $label;

  /**
   * Application Key.
   *
   * @var string
   */
  public $app_key;

  /**
   * Application Secret.
   *
   * @var string
   */
  public $app_sec;

  /**
   * Consumer Key.
   *
   * @var string
   */
  public $con_key;

  /**
   * API Endpint.
   *
   * @var string
   */
  public $endpoint;

}
