<?php

namespace Drupal\blockchain\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Blockchain Node entities.
 */
interface BlockchainNodeInterface extends ConfigEntityInterface {

  const ADDRESS_SOURCE_CLIENT = 'client';

  const ADDRESS_SOURCE_REQUEST = 'request';

  /**
   * Getter for entity type.
   *
   * @return string
   *   Id for entity type.
   */
  public static function entityTypeId();

  /**
   * Getter for ip address.
   *
   * @return string
   *   Value.
   */
  public function getAddress();

  /**
   * Setter for ip/host address.
   *
   * @param string $address
   *   Given ip/host address.
   *
   * @return $this
   *   Chaining.
   */
  public function setAddress($address);

  /**
   * Getter for id.
   *
   * @return string
   *   Value.
   */
  public function getId();

  /**
   * Setter for id.
   *
   * @param string $id
   *   Given ip address.
   *
   * @return $this
   *   Chaining.
   */
  public function setId($id);

  /**
   * Getter for label.
   *
   * @return string
   *   Value.
   */
  public function getLabel();

  /**
   * Setter for label.
   *
   * @param string $label
   *   Given label.
   *
   * @return $this
   *   Chaining.
   */
  public function setLabel($label);

  /**
   * Getter for port.
   *
   * @return string
   *   Value.
   */
  public function getPort();

  /**
   * Setter for port.
   *
   * @param string $port
   *   Given port.
   *
   * @return $this
   *   Chaining.
   */
  public function setPort($port);

  /**
   * Defines if protocol.
   *
   * @return bool
   *   Test result.
   */
  public function isSecure();

  /**
   * Setter for protocol security.
   *
   * @param bool $secure
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setSecure($secure);

  /**
   * Endpoint ready to be requested.
   *
   * @return string
   *   Endpoint.
   */
  public function getEndPoint();

  /**
   * Getter for blockchain type id.
   *
   * @return string
   *   Type id.
   */
  public function getBlockchainTypeId();

  /**
   * Setter for property.
   *
   * @param string $blockchainTypeId
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setBlockchainTypeId($blockchainTypeId);

  /**
   * Getter for blockchain type id.
   *
   * @return BlockchainConfigInterface|null
   *   Type id entity if any.
   */
  public function getBlockchainType();

  /**
   * Setter for property.
   *
   * @param BlockchainConfigInterface $blockchainConfig
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setBlockchainType(BlockchainConfigInterface $blockchainConfig);

  /**
   * Getter for property.
   *
   * @return string
   *   Value.
   */
  public function getAddressSource();

  /**
   * Setter for property.
   *
   * @param string $addressSource
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setAddressSource($addressSource);

  /**
   * Getter for property.
   *
   * @return string
   *   Value.
   */
  public function getSelf();

  /**
   * Setter for property.
   *
   * @param string $self
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setSelf($self);

  /**
   * Generates id based on self and blockchain id.
   *
   * @return string
   *   Generated id.
   */
  public function generateId();

  /**
   * Getter for ip address.
   *
   * @return string
   *   Value.
   */
  public function getIp();

  /**
   * Setter for ip.
   *
   * @param string $ip
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setIp($ip);

  /**
   * Predicate defines if blockchain Node has any data.
   *
   * @return bool
   *   Test result.
   */
  public function hasClientData();

  /**
   * Saves an entity permanently.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public function save();

}
