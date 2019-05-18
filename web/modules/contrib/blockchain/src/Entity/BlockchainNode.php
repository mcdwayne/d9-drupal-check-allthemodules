<?php

namespace Drupal\blockchain\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Blockchain Node entity.
 *
 * @ConfigEntityType(
 *   id = "blockchain_node",
 *   label = @Translation("Blockchain Node"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\blockchain\BlockchainNodeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\blockchain\Form\BlockchainNodeForm",
 *       "edit" = "Drupal\blockchain\Form\BlockchainNodeForm",
 *       "delete" = "Drupal\blockchain\Form\BlockchainNodeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\blockchain\BlockchainNodeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "blockchain_node",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/blockchain_node/{blockchain_node}",
 *     "add-form" = "/admin/structure/blockchain_node/add",
 *     "edit-form" = "/admin/structure/blockchain_node/{blockchain_node}/edit",
 *     "delete-form" = "/admin/structure/blockchain_node/{blockchain_node}/delete",
 *     "collection" = "/admin/structure/blockchain/blockchain_node"
 *   }
 * )
 */
class BlockchainNode extends ConfigEntityBase implements BlockchainNodeInterface {

  /**
   * The Blockchain Node ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Blockchain Node label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Blockchain Node ip/host address.
   *
   * @var string
   */
  protected $address;

  /**
   * Ip address of blockchain node.
   *
   * @var string
   */
  protected $ip;

  /**
   * The Blockchain Node port.
   *
   * @var string
   */
  protected $port;

  /**
   * Defines if protocol is secure.
   *
   * @var bool
   */
  protected $secure;

  /**
   * Blockchain type id.
   *
   * @var string
   */
  protected $blockchainTypeId;

  /**
   * Source of address (client/request)
   *
   * @var string
   */
  protected $addressSource;

  /**
   * The self param.
   *
   * @var string
   */
  protected $self;

  /**
   * {@inheritdoc}
   */
  public static function entityTypeId() {

    return 'blockchain_node';
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {

    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {

    $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress() {

    return $this->address;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($address) {

    $this->address = $address;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {

    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {

    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPort() {

    return $this->port;
  }

  /**
   * {@inheritdoc}
   */
  public function setPort($port) {

    $this->port = $port;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSecure() {

    return $this->secure;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecure($secure) {

    $this->secure = $secure;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndPoint() {

    if ($this->addressSource == static::ADDRESS_SOURCE_CLIENT) {

      return $this->getAddress();
    }
    else {
      $protocol = $this->isSecure() ? 'https://' : 'http://';
      $protocol = is_null($this->isSecure()) ? '' : $protocol;
      $port = $this->getPort() ? ':' . $this->getPort() : '';

      return $protocol . $this->getAddress() . $port;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockchainTypeId() {

    return $this->blockchainTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockchainTypeId($blockchainTypeId) {

    $this->blockchainTypeId = $blockchainTypeId;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockchainType() {

    if ($this->blockchainTypeId) {

      return BlockchainConfig::load($this->blockchainTypeId);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockchainType(BlockchainConfigInterface $blockchainConfig) {

    return $this->setBlockchainTypeId($blockchainConfig->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressSource() {

    return $this->addressSource;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressSource($addressSource) {

    $this->addressSource = $addressSource;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelf() {

    return $this->self;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelf($self) {

    $this->self = $self;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function generateId() {

    return $this->getBlockchainTypeId() . '_' . $this->getSelf();
  }

  /**
   * {@inheritdoc}
   */
  public function getIp() {

    return $this->ip;
  }

  /**
   * {@inheritdoc}
   */
  public function setIp($ip) {

    $this->ip = $ip;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasClientData() {

    return !is_null($this->getIp()) &&
      !is_null($this->getPort()) &&
      !is_null($this->isSecure());
  }

}
