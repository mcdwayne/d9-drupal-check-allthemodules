<?php

namespace Drupal\entity_pilot\Data;

use Defuse\Crypto\Encoding;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Drupal\Component\Serialization\Json;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\Encryption\Encrypter;
use Drupal\entity_pilot\Exception\EncryptionException;
use Drupal\entity_pilot\TransportInterface;

/**
 * Defines a value object for a flight object sent/received from EntityPilot.
 */
class FlightManifest implements FlightManifestInterface {

  const DRUPAL_VERSION = '2.0';
  /**
   * Remote ID.
   *
   * @var int
   */
  protected $id;

  /**
   * Flight identifier.
   *
   * @var string
   */
  protected $info;

  /**
   * Array of normalized encrypted contents for the flight.
   *
   * @var array
   */
  protected $contents;

  /**
   * Account ID (carrier ID).
   *
   * @var int
   */
  protected $account;

  /**
   * Source site.
   *
   * @var string
   */
  protected $site;

  /**
   * Revision Log.
   *
   * @var string
   */
  protected $log;

  /**
   * Timestamp of change in UTC.
   *
   * @var int
   */
  protected $changed;

  /**
   * Black box key (password).
   *
   * @var string
   */
  protected $blackBoxKey;

  /**
   * Count of entities in flight.
   *
   * @var int
   */
  protected $count;

  /**
   * Field mapping.
   *
   * @var array
   */
  protected $fieldMapping = [];

  /**
   * {@inheritdoc}
   */
  public static function fromArray(array $records, $decryption_secret) {
    $return = [];
    foreach ($records as $flight_details) {
      if (!isset($flight_details['id'])) {
        throw new \UnexpectedValueException('Cannot bulk create manifests for a flight without an ID.');
      }
      if (!empty($flight_details['contents'])) {
        $contents_payload = Json::decode($flight_details['contents']);
        $contents = [];
        foreach ($contents_payload as $uuid => $encrypted_entity) {
          try {
            if (version_compare($flight_details['drupal_version'], self::DRUPAL_VERSION) === -1) {
              $contents[$uuid] = Json::decode(Encrypter::legacyDecrypt($decryption_secret, base64_decode($encrypted_entity)));
            }
            else {
              $contents[$uuid] = Json::decode(Encrypter::decrypt($decryption_secret, Encoding::hexToBin($encrypted_entity)));
            }
          }
          catch (BadFormatException $e) {
            throw EncryptionException::forUuid($e, $uuid);
          }
          catch (EnvironmentIsBrokenException $e) {
            throw EncryptionException::forUuid($e, $uuid);
          }
          catch (WrongKeyOrModifiedCiphertextException $e) {
            throw EncryptionException::forUuid($e, $uuid);
          }
        }
        $flight_details['contents'] = $contents;
      }
      if (!empty($flight_details['fields'])) {
        $flight_details['fieldMapping'] = Json::decode($flight_details['fields']);
      }
      $flight = static::create($flight_details);
      $return[$flight->getRemoteId()] = $flight;
    }
    return $return;
  }

  /**
   * Factory method to build flight manifest from an arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $arrival
   *   Arrival to create flight from.
   *
   * @return static
   *   New static.
   */
  public static function fromArrival(ArrivalInterface $arrival) {
    return self::create([
      'account' => $arrival->getAccount()->id(),
      'contents' => Json::decode($arrival->getContents()),
      'fieldMapping' => $arrival->getFieldMap(),
      'id' => $arrival->getRemoteId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlackBoxKey() {
    return $this->blackBoxKey;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    $manifest = new static();
    foreach ($values as $key => $value) {
      $manifest->{$key} = $value;
    }
    return $manifest;
  }

  /**
   * {@inheritdoc}
   */
  public function setCarrierId($account_id) {
    $this->account = $account_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarrierId() {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function setContents(array $contents) {
    $this->contents = $contents;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents($encoded = FALSE) {
    if (!$encoded) {
      return $this->contents;
    }
    return Json::encode($this->contents);
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlackBoxKey($key) {
    $this->blackBoxKey = $key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setInfo($info) {
    $this->info = $info;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return $this->info;
  }

  /**
   * {@inheritdoc}
   */
  public function setLog($log) {
    $this->log = $log;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLog() {
    return $this->log;
  }

  /**
   * {@inheritdoc}
   */
  public function setSite($site) {
    $this->site = $site;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSite() {
    return $this->site;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray($encryption_secret) {
    $date = new \DateTime('@' . $this->changed, new \DateTimeZone('UTC'));
    $contents = [];
    // Keep the UUID unencrypted.
    foreach ($this->contents as $uuid => $entity) {
      try {
        $contents[$uuid] = Encoding::binToHex(Encrypter::encrypt($encryption_secret, Json::encode($entity)));
      }
      catch (BadFormatException $e) {
        throw EncryptionException::forUuid($e, $uuid);
      }
      catch (EnvironmentIsBrokenException $e) {
        throw EncryptionException::forUuid($e, $uuid);
      }
    }
    return [
      'log' => $this->log,
      'contents' => Json::encode($contents),
      'changed' => [
        'date' => [
          'year' => (int) $date->format('Y'),
          'month' => (int) $date->format('m'),
          'day' => (int) $date->format('d'),
        ],
        'time' => [
          'hour' => (int) $date->format('H'),
          'minute' => (int) $date->format('i'),
        ],
      ],
      'account' => $this->account,
      'site' => $this->site,
      'info' => $this->info,
      'fields' => Json::encode($this->fieldMapping),
      'drupal_version' => self::DRUPAL_VERSION,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setChanged($changed) {
    $this->changed = $changed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanged() {
    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransposedContents($new_site, TransportInterface $transport, AccountInterface $account) {
    if (!isset($this->contents)) {
      // We need to fetch contents from Entity Pilot.
      $this->fetchContents($transport, $account);
    }
    $contents = $this->getContents(TRUE);
    return str_replace(str_replace('"', '', Json::encode($this->site)), str_replace('"', '', Json::encode($new_site)), $contents);
  }

  /**
   * Fetch contents and field map from Entity Pilot.
   *
   * When the list of flights is queried, it doesn't contain the contents and
   * field-map to save on bandwidth. We populate a partial flight with full
   * values here.
   *
   * @param \Drupal\entity_pilot\TransportInterface $transport
   *   Transport interface.
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   Entity Pilot account.
   */
  protected function fetchContents(TransportInterface $transport, AccountInterface $account) {
    $complete_flight = $transport->getFlight($this->getRemoteId(), $account);
    $this->setContents($complete_flight->getContents());
    $this->setFieldMapping($complete_flight->getFieldMapping());
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping($encoded = FALSE) {
    $mapping = $this->fieldMapping;
    if ($encoded) {
      $mapping = Json::encode($mapping);
    }
    return $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldMapping(array $field_mapping) {
    $this->fieldMapping = $field_mapping;
    return $this;
  }

}
