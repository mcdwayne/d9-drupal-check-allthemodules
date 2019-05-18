<?php

namespace Drupal\entity_pilot_git;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot\Encryption\Encrypter;
use Drupal\entity_pilot\Transport;
use Drupal\hal\LinkManager\TypeLinkManagerInterface;

/**
 * Class GitTransport.
 *
 * @package Drupal\entity_pilot_git
 */
class GitTransport extends Transport {

  /**
   * Config factory service.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed link manager service.
   *
   * @var \Drupal\hal\LinkManager\TypeLinkManagerInterface
   */
  protected $typeLinkManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The base dir for exports.
   *
   * @var string
   */
  protected $baseDir;

  /**
   * The directory for exporting manifest files.
   *
   * @var string
   */
  protected $manifestDir;

  /**
   * GitTransport constructor.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The json serializer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\hal\LinkManager\TypeLinkManagerInterface $type_link_manager
   *   The type link manager from the HAL module.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(SerializationInterface $serializer, ConfigFactoryInterface $config_factory, TypeLinkManagerInterface $type_link_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->jsonEncoder = $serializer;
    $this->configFactory = $config_factory;
    $this->typeLinkManager = $type_link_manager;
    $this->entityTypeManager = $entity_type_manager;
    $export_dir = $this->configFactory->get('entity_pilot_git.settings')->get('export_directory');

    $this->baseDir = DRUPAL_ROOT . '/' . $export_dir;
    $this->manifestDir = sprintf('%s/manifests', $this->baseDir);
  }

  /**
   * {@inheritdoc}
   *
   * TODO: abstract file_put_contents so we can test.
   */
  public function sendFlight(FlightManifestInterface $manifest, $secret) {

    $this->checkDir($this->baseDir);

    // Export each passenger to individual files.
    $manifest_digest = [];
    $count = 0;
    foreach ($manifest->getContents() as $uuid => $passenger) {
      $entity_uri = $passenger['_links']['type']['href'];
      $type = $this->typeLinkManager->getTypeInternalIds($entity_uri);
      $entity_type = $this->entityTypeManager->getDefinition($type['entity_type']);

      // Remove id and revision fields.
      $id_field = $entity_type->getKey('id');
      unset($passenger[$id_field]);
      $revision_field = $entity_type->getKey('revision');
      if ($revision_field) {
        unset($passenger[$revision_field]);
      }

      $entity_type_dir = $this->baseDir . '/' . $type['entity_type'] . '/' . $type['bundle'];
      $this->checkDir($entity_type_dir);
      $manifest_digest[$type['entity_type']][$type['bundle']][] = $uuid;

      $export_path = sprintf('%s/%s.json', $entity_type_dir, $uuid);
      file_put_contents($export_path, $this->jsonEncode($passenger));
      $count++;
    }

    // TODO: Remove once we can store secret in config or otherwise.
    $secret = hex2bin($secret);
    $manifest_array = $manifest->toArray($secret);
    $manifest_array['contents'] = $manifest_digest;
    $manifest_array['count'] = $count;

    $timestamp = time();
    $manifest_array['id'] = $timestamp;
    $manifest_array['changed'] = "@" . (string) $timestamp;

    $this->checkDir($this->manifestDir);
    $export_path = sprintf('%s/%s.json', $this->manifestDir, $timestamp);
    file_put_contents($export_path, $this->jsonEncode($manifest_array));

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function queryFlights(AccountInterface $account, $search_string = '', $limit = 50, $offset = 0) {
    $options = [
      'search' => $search_string,
      'offset' => $offset,
      'limit' => $limit,
    ];

    return $this->performGitRequest($this->manifestDir, $account, FALSE, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlight($remote_id, AccountInterface $account) {
    $options['remote_id'] = $remote_id;
    $flights = $this->performGitRequest($this->manifestDir, $account, TRUE, $options);
    // Return the specific flight.
    if (!empty($flights[$remote_id])) {
      return $flights[$remote_id];
    }
    return reset($flights);
  }

  /**
   * Checks git for manifest files, loads contents if $single_record.
   *
   * @param string $path
   *   The path to search under.
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   The account to use.
   * @param bool $single_record
   *   Whether we are fetching a single record, if so we gather the passengers.
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface[]
   *   An array of flights.
   *
   * @throws \Exception
   *   When a manifest file was invalid JSON.
   */
  protected function performGitRequest($path, AccountInterface $account, $single_record = FALSE, array $options = []) {
    // Get the manifests.
    $files = file_scan_directory($path, '/.*\.json/');
    $secret = hex2bin($account->getSecret());

    // Order the files by key as they are named by timestamp.
    ksort($files);

    // Slice the array by the limit and offset.
    if (isset($options['limit']) && isset($options['offset'])) {
      $files = array_slice($files, $options['offset'], $options['limit']);
    }

    $flights = [];
    foreach ($files as $file) {
      $manifest = file_get_contents($file->uri);
      $manifest_array = $this->jsonEncoder->decode($manifest);
      // TODO: Inject this.
      if (empty($manifest_array)) {
        throw new \Exception(sprintf('Manifest file %s failed to decode from JSON.', $file->uri));
      }

      // Only gather passengers when we are getting a specific flight.
      if ($single_record && $options['remote_id'] == $manifest_array['id']) {
        $this->gatherPassengers($manifest_array, $secret);
      }
      else {
        // Empty the contents for the arrival add screen.
        unset($manifest_array['contents']);
      }

      $flights[$manifest_array['id']] = $manifest_array;
    }

    // TODO: Remove once we can store secret in config or otherwise.
    return FlightManifest::fromArray($flights, $secret);
  }

  /**
   * Gathers all content referenced by a manifest.
   *
   * This has to format the manifest contents into something
   * FlightManifest::fromArray expects.
   *
   * @param array $manifest_array
   *   The manifest array.
   * @param string $secret
   *   The encryption secret.
   */
  private function gatherPassengers(array &$manifest_array, $secret) {
    $passengers = [];
    foreach ($manifest_array['contents'] as $entity_type => $bundles) {
      foreach ($bundles as $bundle => $uuids) {
        foreach ($uuids as $uuid) {
          $entity_type_dir = $this->baseDir . '/' . $entity_type . '/' . $bundle;

          $entity_path = sprintf('%s/%s.json', $entity_type_dir, $uuid);
          // Continue if a manifest references an old file that doesn't exist.
          if (!file_exists($entity_path)) {
            continue;
          }
          $passenger = file_get_contents($entity_path);

          $passengers[$uuid] = base64_encode(Encrypter::encrypt($secret, $passenger));
        }
      }
    }

    // Override the contents with a normalized json array.
    $manifest_array['contents'] = $this->jsonEncoder->encode($passengers);
  }

  /**
   * Encode a value into json given a series of params.
   *
   * Mimics Json::encode() but adds the JSON_PRETTY_PRINT option.
   *
   * @param array $value
   *   An array of values to encode.
   *
   * @return string
   *   Encoded json.
   */
  private function jsonEncode(array $value) {
    return json_encode($value, JSON_HEX_QUOT | JSON_PRETTY_PRINT);
  }

  /**
   * Creates/modifies permissions on a directory.
   *
   * @param string $directory
   *   The directory to check.
   *
   * @throws \Exception
   *   If the modification couldn't be made.
   */
  private function checkDir($directory) {
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      throw new \Exception(
        sprintf('The directory "%s" could not be created.', $directory)
      );
    }
  }

}
