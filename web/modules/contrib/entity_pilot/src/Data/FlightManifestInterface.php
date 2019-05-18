<?php

namespace Drupal\entity_pilot\Data;

use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\TransportInterface;

/**
 * Defines an interface for a flight object sent/received from EntityPilot.
 */
interface FlightManifestInterface {

  /**
   * Sets the description of the flight.
   *
   * @param string $info
   *   Flight description.
   *
   * @return self
   *   Instance method was called on.
   */
  public function setInfo($info);

  /**
   * Sets the account ID.
   *
   * @param int $account_id
   *   The account ID.
   *
   * @return self
   *   Instance method was called on.
   */
  public function setCarrierId($account_id);

  /**
   * Gets the account ID.
   *
   * @return int
   *   The account ID.
   */
  public function getCarrierId();

  /**
   * Gets the log message.
   *
   * @return string
   *   The log message.
   */
  public function getLog();

  /**
   * Gets the contents.
   *
   * @param bool $encoded
   *   TRUE to encode contents and return as string.
   *
   * @return array|string
   *   Contents of manifest.
   */
  public function getContents($encoded = FALSE);

  /**
   * Sets the site URI.
   *
   * @param string $site
   *   The site uri.
   *
   * @return self
   *   instance method was called on.
   */
  public function setSite($site);

  /**
   * Sets the normalized contents.
   *
   * @param array $contents
   *   Normalized contents.
   *
   * @return self
   *   Instance method was called on.
   */
  public function setContents(array $contents);

  /**
   * Returns the Remote ID.
   *
   * @return int
   *   The remote ID of the flight.
   */
  public function getRemoteId();

  /**
   * Gets the flight description.
   *
   * @return string
   *   The flight description.
   */
  public function getInfo();

  /**
   * Sets the Remote ID.
   *
   * @param int $id
   *   Remote ID.
   *
   * @return self
   *   Instance method was called on.
   */
  public function setRemoteId($id);

  /**
   * Converts the manifest into an array object.
   *
   * @param string $encryption_secret
   *   Secret key for encryption.
   *
   * @return array
   *   Array representation of the object.
   *
   * @throws \Drupal\entity_pilot\Exception\EncryptionException
   *   When encryption is not possible.
   */
  public function toArray($encryption_secret);

  /**
   * Factory method to create object.
   *
   * @param array $values
   *   Array of values to initialize the manifest with.
   *
   * @return self
   *   New instance.
   */
  public static function create(array $values = []);

  /**
   * Turns an array of raw records into an array of flight manifests.
   *
   * @param array $records
   *   Array of records.
   * @param string $decryption_secret
   *   Decryption secret.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface[]
   *   Array of flight manifests.
   *
   * @throws \Drupal\entity_pilot\Exception\EncryptionException
   *   When encryption is not possible.
   */
  public static function fromArray(array $records, $decryption_secret);

  /**
   * Gets the site URI.
   *
   * @return string
   *   The site URI.
   */
  public function getSite();

  /**
   * Sets the log message for the flight.
   *
   * @param string $log
   *   The log message.
   *
   * @return self
   *   Instance method was called on.
   */
  public function setLog($log);

  /**
   * Sets the black box key for the flight.
   *
   * @param string $key
   *   The black box key..
   *
   * @return self
   *   Instance method was called on.
   */
  public function setBlackBoxKey($key);

  /**
   * Returns the black box key.
   *
   * @return string
   *   The black box key.
   */
  public function getBlackBoxKey();

  /**
   * Sets the changed timestamp.
   *
   * @param int $changed
   *   Changed timestamp.
   *
   * @return self
   *   Instance the method was called on.
   */
  public function setChanged($changed);

  /**
   * Gets the changed timestamp.
   *
   * @return int
   *   The changed timestamp
   */
  public function getChanged();

  /**
   * Gets the content with references to the old site updated to the new site.
   *
   * @param string $new_site
   *   New site to set.
   * @param \Drupal\entity_pilot\TransportInterface $transport
   *   Transport service.
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   Account for this flight.
   *
   * @return array|string
   *   Contents of manifest.
   */
  public function getTransposedContents($new_site, TransportInterface $transport, AccountInterface $account);

  /**
   * Gets the number of entities in the flight.
   *
   * @return int
   *   Number of entities.
   */
  public function getCount();

  /**
   * Gets field mapping.
   *
   * @param bool $encoded
   *   TRUE to JSON encode the field map.
   *
   * @return array
   *   Array of fields keyed by entity-type.
   */
  public function getFieldMapping($encoded = FALSE);

  /**
   * Sets field mapping.
   *
   * @param array $map
   *   New field map. Array keyed by entity-type.
   *
   * @return self
   *   Instance called.
   */
  public function setFieldMapping(array $map);

}
