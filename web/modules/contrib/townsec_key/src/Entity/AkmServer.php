<?php

namespace Drupal\townsec_key\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

use TownsendSecurity;

use Drupal\townsec_key\AkmServerInterface;

/**
 * @ConfigEntityType(
 *   id = "akm_server",
 *   label = @Translation("AKM Key Server"),
 *   handlers = {
 *     "list_builder" = "Drupal\townsec_key\Controller\AkmServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\townsec_key\Form\AkmServerForm",
 *       "edit" = "Drupal\townsec_key\Form\AkmServerForm",
 *       "delete" = "Drupal\townsec_key\Form\AkmServerDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer site configuration",
 *   config_prefix = "akm_server",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/system/akm-server/add",
 *     "edit-form" = "/admin/config/system/akm-server/{akm_server}",
 *     "delete-form" = "/admin/config/system/akm-server/{akm_server}/delete",
 *     "collection" = "/admin/config/system/akm-server"
 *   }
 * )
 */
class AkmServer extends ConfigEntityBase implements AkmServerInterface {

  /** @var string */
  public $label;

  /** @var string */
  public $name;

  /** @var string */
  public $host;

  /** @var string */
  public $local_cert;

  /** @var string */
  public $cafile;

  /** @var integer */
  public $user_port = 6000;

  /** @var integer */
  public $encrypt_port = 6003;

  /** @var integer */
  public $weight = 1;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyServer() {
    return new TownsendSecurity\KeyServer(
      $this->name,
      $this->host,
      realpath($this->local_cert),
      realpath($this->cafile),
      [
        'user' => $this->user_port,
        'encrypt' => $this->encrypt_port,
      ]
    );
  }

}
