<?php

namespace Drupal\blockchain\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Blockchain Block entities.
 *
 * @ingroup blockchain
 */
interface BlockchainBlockInterface extends ContentEntityInterface {

  /**
   * Gets the Blockchain Block creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Blockchain Block.
   */
  public function getCreatedTime();

  /**
   * Sets the Blockchain Block creation timestamp.
   *
   * @param int $timestamp
   *   The Blockchain Block creation timestamp.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface
   *   The called Blockchain Block entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Getter for author name.
   *
   * @return string|null
   *   Author name,
   */
  public function getAuthor();

  /**
   * Setter for author.
   *
   * @param string|null $author
   *   Author name.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface
   *   Chaining.
   */
  public function setAuthor($author);

  /**
   * Getter for data.
   *
   * @return string|null
   *   Value.
   */
  public function getData();

  /**
   * Setter for data.
   *
   * @param mixed $data
   *   String serialised data.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface
   *   Chaining.
   */
  public function setData($data);

  /**
   * Getter for nonce.
   *
   * @return string|null
   *   Value.
   */
  public function getNonce();

  /**
   * Setter for nonce.
   *
   * @param string $nonce
   *   Value.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface
   *   Chaining.
   */
  public function setNonce($nonce);

  /**
   * Getter for hash.
   *
   * @return string|null
   *   String value.
   */
  public function getPreviousHash();

  /**
   * Setter for hash.
   *
   * @param string $hash
   *   Hash.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface
   *   Chaining.
   */
  public function setPreviousHash($hash);

  /**
   * Getter for timestamp.
   *
   * @return int|null
   *   Timestamp.
   */
  public function getTimestamp();

  /**
   * Setter for timestamp.
   *
   * @param int $timestamp
   *   Timestamp.
   *
   * @return \Drupal\blockchain\Entity\BlockchainBlockInterface
   *   Chaining.
   */
  public function setTimestamp($timestamp);

  /**
   * Getter for block hash from object.
   *
   * @return string
   *   Hash, provided by Util class.
   */
  public function toHash();

  /**
   * Overrides save handler definition.
   *
   * @return int
   *   Save result.
   */
  public function save();

  /**
   * Overrides deletes handler definition.
   *
   * @return mixed
   *   Delete result.
   */
  public function delete();

  /**
   * Comparator.
   *
   * @param BlockchainBlockInterface $blockchainBlock
   *   Block to compare.
   *
   * @return bool
   *   Compare result.
   */
  public function equals(BlockchainBlockInterface $blockchainBlock);

}
