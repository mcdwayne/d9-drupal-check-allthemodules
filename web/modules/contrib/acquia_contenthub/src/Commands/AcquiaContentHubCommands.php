<?php

namespace Drupal\acquia_contenthub\Commands;

use Acquia\ContentHubClient\CDF\CDFObject;
use Acquia\ContentHubClient\CDFDocument;
use Acquia\ContentHubClient\ContentHubClient;
use Acquia\ContentHubClient\Settings;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\EntityCDFSerializer;
use Drupal\acquia_contenthub\Form\ContentHubSettingsForm;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\depcalc\DependencyCalculator;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Drush\Commands\DrushCommands;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drush\Log\LogLevel;
use Symfony\Component\Console\Helper\Table;

/**
 * Class AcquiaContentHubCommands.
 *
 * @package Drupal\acquia_contenthub\Commands
 */
class AcquiaContentHubCommands extends DrushCommands {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The client factory.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * The dependency calculator.
   *
   * @var \Drupal\depcalc\DependencyCalculator
   */
  protected $calculator;

  /**
   * The CDF Serializer.
   *
   * @var \Drupal\acquia_contenthub\EntityCDFSerializer
   */
  protected $serializer;

  /**
   * AcquiaContenthubCommands constructor.
   *
   * @param \Drupal\depcalc\DependencyCalculator $calculator
   *   The dependency calculator.
   * @param \Drupal\acquia_contenthub\EntityCDFSerializer $serializer
   *   The dependency calculator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $client_factory
   *   The client factory.
   */
  public function __construct(DependencyCalculator $calculator, EntityCDFSerializer $serializer, ConfigFactoryInterface $config_factory, ClientFactory $client_factory) {
    $this->configFactory = $config_factory;
    $this->clientFactory = $client_factory;
    $this->calculator = $calculator;
    $this->serializer = $serializer;
  }

  /**
   * Generates a CDF Document from a manifest file.
   *
   * @param string $manifest
   *   The location of the manifest file.
   *
   * @command acquia:contenthub-export-local-cdf
   * @aliases ach-elc
   *
   * @return false|string
   * @throws \Exception
   */
  public function exportCdf($manifest) {
    if (!file_exists($manifest)) {
      throw new \Exception("The provided manifest file does not exist in the specified location.");
    }
    $manifest = Yaml::decode(file_get_contents($manifest));
    $entities = [];
    $entityTypeManager = \Drupal::entityTypeManager();
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = \Drupal::service('entity.repository');
    foreach ($manifest['entities'] as $entity) {
      list($entity_type_id, $entity_id) = explode(":", $entity);
      if (!Uuid::isValid($entity_id)) {
        $entities[] = $entityTypeManager->getStorage($entity_type_id)->load($entity_id);
        continue;
      }
      $entities[] = $repository->loadEntityByUuid($entity_type_id, $entity_id);
    }
    if (!$entities) {
      throw new \Exception("No entities loaded from the manifest.");
    }
    /** @var \Drupal\acquia_contenthub\ContentHubCommonActions $common */
    $common = \Drupal::service('acquia_contenthub_common_actions');
    return $common->getLocalCdfDocument(...$entities)->toString();
  }

  /**
   * Imports entities from a CDF Document.
   *
   * @param string $location
   *   The location of the cdf file.
   *
   * @command acquia:contenthub-import-local-cdf
   * @aliases ach-ilc
   *
   * @return void
   * @throws \Exception
   */
  public function importCdf($location) {
    if (!file_exists($location)) {
      throw new \Exception("The cdf to import was not found in the specified location.");
    }
    $json = file_get_contents($location);
    $data = Json::decode($json);
    $document_parts = [];
    foreach ($data['entities'] as $entity) {
      $document_parts[] = CDFObject::fromArray($entity);
    }
    $cdf_document = new CDFDocument(...$document_parts);

    /** @var \Drupal\acquia_contenthub\ContentHubCommonActions $common */
    $common = \Drupal::service('acquia_contenthub_common_actions');
    $stack = $common->importEntityCdfDocument($cdf_document);
    $this->output->writeln(dt("Imported @items from @location.", [
      '@items' => count($stack->getDependencies()),
      '@location' => $location,
    ]));
  }

  /**
   * Retrieves an Entity from a local source or contenthub.
   *
   * @param string $op
   *   The operation being performed.
   * @param string $uuid
   *   Entity identifier or entity's UUID.
   * @param string $entity_type
   *   The entity type in case of local retrieval.
   *
   * @command acquia:contenthub-entity
   * @aliases ach-ent
   *
   * @throws \Exception
   */
  public function contenthubEntity($op, $uuid, $entity_type = NULL) {
    $client = $this->clientFactory->getClient();

    if (empty($uuid)) {
      throw new \Exception("Please supply the uuid of the entity you want to retrieve.");
    }

    switch ($op) {
      case 'local':
        if (empty($entity_type)) {
          throw new \Exception(dt("Entity_type is required for local entities"));
        }
        $repository = \Drupal::service('entity.repository');
        $entity = $repository->loadEntityByUuid($entity_type, $uuid);

        $wrapper = new DependentEntityWrapper($entity);
        $stack = new DependencyStack();
        $this->calculator->calculateDependencies($wrapper, $stack);
        $entities = NestedArray::mergeDeep(
          [$wrapper->getEntity()->uuid() => $wrapper],
          $stack->getDependenciesByUuid(array_keys($wrapper->getDependencies()))
        );
        $objects = $this->serializer->serializeEntities(...array_values($entities));
        $data = [];
        foreach ($objects as $object) {
          $data[$object->getUuid()] = $object->toArray();
        }
        $json = json_encode(
          $data,
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $this->output()->writeln($json);
        break;

      case 'remote':
        $json = json_encode(
          $client->getEntity($uuid)->toArray(),
          JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $this->output()->writeln($json);
        break;

      default:
        // Invalid operation.
        throw new \Exception(dt('The op "@op" is invalid', ['@op' => $op]));
    }
  }

  /**
   * Prints the CDF from a local source (drupal site)
   *
   * @param string $entity_type
   *   The entity type to load.
   * @param string $entity_id
   *   The entity identifier or entity's UUID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command acquia:contenthub-local
   * @aliases ach-lo,acquia-contenthub-local
   *
   * @deprecated
   */
  public function contenthubLocal($entity_type, $entity_id) {
    $entity_type_manager = \Drupal::entityTypeManager();

    /** @var \Drupal\Core\Entity\EntityRepository $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    if (empty($entity_type) || empty($entity_id)) {
      throw new \Exception(dt("Missing required parameters: entity_type and entity_id (or entity's uuid)"));
    }
    elseif (!$entity_type_manager->getDefinition($entity_type)) {
      throw new \Exception(dt("Entity type @entity_type does not exist", [
        '@entity_type' => $entity_type,
      ]));
    }
    else {
      if (Uuid::isValid($entity_id)) {
        $entity = $entity_repository->loadEntityByUuid($entity_type, $entity_id);
      }
      else {
        $entity = $entity_type_manager->getStorage($entity_type)->load($entity_id);
      }
    }
    if (!$entity) {
      $this->output()->writeln(dt("Entity having entity_type = @entity_type and entity_id = @entity_id does not exist.", [
        '@entity_type' => $entity_type,
        '@entity_id' => $entity_id,
      ]));
    }
    // If nothing else, return our object structure.
    $this->contenthubEntity('local', $entity->uuid(), $entity_type);
  }

  /**
   * Prints the CDF from a remote source (Content Hub)
   *
   * @param string $uuid
   *   The entity's UUID.
   *
   * @command acquia:contenthub-remote
   * @aliases ach-re,acquia-contenthub-remote
   *
   * @throws \Exception
   */
  public function contenthubRemote($uuid) {
    if (FALSE === Uuid::isValid($uuid)) {
      throw new \Exception(dt("Argument provided is not a UUID."));
    }

    $this->contenthubEntity('remote', $uuid);
  }

  /**
   * List entities from the Content Hub using the listEntities() method.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option limit
   *   The number of entities to be listed.
   * @option start
   *   The offset to start listing the entities (Useful for pagination).
   * @option origin
   *   The Client's Origin UUID.
   * @option language
   *   The Language that will be used to filter field values.
   * @option attributes
   *   The attributes to display for all listed entities
   * @option type
   *   The entity type
   * @option filters
   *   Filters entities according to a set of of conditions as a key=value pair
   *   separated by commas. You could use regex too.
   *
   * @command acquia:contenthub-list
   * @aliases ach-list,acquia-contenthub-list
   *
   * @return mixed
   *   Content Hub list.
   *
   * @throws \Exception
   */
  public function contenthubList(array $options = ['limit' => NULL, 'start' => NULL, 'origin' => NULL, 'language' => NULL, 'attributes' => NULL, 'type' => NULL, 'filters' => NULL,]) { // @codingStandardsIgnoreLine.
    $client = $this->clientFactory->getClient();
    if (!$client) {
      throw new \Exception(dt('Error trying to connect to the Content Hub. Make sure this site is registered to Content hub.'));
    }
    $list_options = [];

    // Obtaining the limit.
    $limit = $options['limit'];
    if (isset($limit)) {
      $limit = (int) $limit;
      if ($limit < 1 || $limit > 1000) {
        throw new \Exception(dt("The limit has to be an integer from 1 to 1000."));
      }
      else {
        $list_options['limit'] = $limit;
      }
    }

    // Obtaining the offset.
    $start = $options['start'];
    if (isset($start)) {
      if (!is_numeric($start)) {
        throw new \Exception(dt("The start offset has to be numeric starting from 0."));
      }
      else {
        $list_options['start'] = $start;
      }
    }

    // Filtering by origin.
    $origin = $options['origin'];
    if (isset($origin)) {
      if (Uuid::isValid($origin)) {
        $list_options['origin'] = $origin;
      }
      else {
        throw new \Exception(dt("The origin has to be a valid UUID."));
      }
    }

    // Filtering by language.
    // TODO: Add a query to validate languages in plexus.
    $language = $options['language'];
    if (isset($language)) {
      $list_options['language'] = $language;
    }

    // Filtering by fields.
    $fields = $options['attributes'];
    if (isset($fields)) {
      $list_options['fields'] = $fields;
    }

    // Filtering by type.
    $type = $options['type'];
    if (isset($type)) {
      $list_options['type'] = $type;
    }

    // Building the filters.
    $filters = $options['filters'];
    if (isset($filters)) {
      $filters = isset($filters) ? explode(",", $filters) : FALSE;
      foreach ($filters as $key => $filter) {
        list($name, $value) = explode("=", $filter);
        $filters[$name] = $value;
        unset($filters[$key]);
      }
      $list_options['filters'] = $filters;
    }

    $list = $client->listEntities($list_options);
    $this->output()->writeln(print_r($list, TRUE));
  }

  /**
   * Deletes a single entity from the Content Hub.
   *
   * @param string $uuid
   *   The entity's UUID.
   *
   * @command acquia:contenthub-delete
   * @aliases ach-del,acquia-contenthub-delete
   *
   * @throws \Exception
   */
  public function contenthubDelete($uuid) {
    if (!$this->io()->confirm(dt('Are you sure you want to delete the entity with uuid = @uuid from the Content Hub? There is no way back from this action!', [
      '@uuid' => $uuid,
    ]))) {
      return;
    }

    /** @var \Drupal\acquia_contenthub\ContentHubCommonActions $common */
    $common = \Drupal::service('acquia_contenthub_common_actions');
    if ($common->deleteRemoteEntity($uuid)) {
      $this->output()->writeln(dt('Entity with UUID = @uuid has been successfully deleted from the Content Hub.', [
        '@uuid' => $uuid,
      ]));
      return;
    }
    $this->output()->writeln(dt('WARNING: Entity with UUID = @uuid has NOT been successfully deleted from the Content Hub.', [
      '@uuid' => $uuid,
    ]));
  }

  /**
   * Purges all entities from Acquia Content Hub.
   *
   * WARNING! Be VERY careful when using this command.
   * This destructive command requires elevated keys. Every
   * subsequent execution of this command will override the backup created
   * by the previous call.
   *
   * @param string $api
   *   The API key.
   * @param string $secret
   *   The secret key.
   *
   * @command acquia:contenthub-purge
   *
   * @aliases ach-purge,acquia-contenthub-purge
   *
   * @throws \Exception
   */
  public function contenthubPurge($api = NULL, $secret = NULL) {

    $client = $this->clientFactory->getClient();

    // Use the keys associated with Drupal config explicitly entered.
    if (!empty($api) && !empty($secret)) {
      $client = $this->resetConnection($client, $api, $secret);
    }

    // Without a client, we cannot purge.
    if (!$client) {
      throw new \Exception(dt('Error trying to connect to the Content Hub. Make sure this site is registered to Content hub.'));
    }

    // Get the remote settings for the UUID (name) of the client.
    $settings = $client->getRemoteSettings();

    // Warning prompt initially.
    $warning_message = "Are you sure you want to PURGE your Content Hub Subscription?\n" .
      "*************************************************************************************\n" .
      "PROCEED WITH CAUTION. THIS ACTION WILL PURGE ALL EXISTING ENTITIES IN YOUR CONTENT HUB SUBSCRIPTION.\n" .
      "While a backup is created for use by the restore command, restoration may not be timely and is not guaranteed. Concurrent or frequent\n" .
      "use of this command may result in an inability to restore. You can always republish your content as a means of 'recovery'.
    For more information, check https://docs.acquia.com/content-hub.\n" .
      "*************************************************************************************\n" .
      "Are you sure you want to proceed?\n";

    // If user aborts, stop the purge.
    if (!$this->io()->confirm($warning_message)) {
      return;
    }

    // Make sure this is the correct account before purging.
    $double_check_message = dt("Are you ABSOLUTELY sure? Purging the subscription !sub will remove all entities from Content Hub. Backups are created but not guaranteed. Please confirm one last time that you would like to continue.",
      [
        '!sub' => $settings['uuid'],
      ]);

    // If user aborts, stop the purge.
    if (!$this->io()->confirm($double_check_message)) {
      return;
    }

    // Execute the 'purge' command.
    $response = $client->purge();

    // Success but not really.
    if (!(isset($response['success'])) || $response['success'] !== TRUE) {
      $message = dt("Error trying to purge your subscription. You might require elevated keys to perform this operation.");

      throw new \Exception($message);
    }

    // Error occurred.
    if (!empty($response['error']['code']) && !empty($response['error']['message'])) {
      $message = dt('Error trying to purge your subscription. Status code !code. !message',
        [
          '!code' => $response['error']['code'],
          '!message' => $response['error']['message'],
        ]);

      throw new \Exception($message);
    }

    $confirmation_message = dt("Your !sub subscription is being purged. All clients who have registered to received webhooks will be notified with purge and reindex webhooks when the purge process has been completed.\n",
      [
        '!sub' => $settings['uuid'],
      ]);

    $this->output()->writeln($confirmation_message);
  }

  /**
   * Restores the backup taken by a previous execution of the "purge" command.
   *
   * WARNING! Be VERY careful when using this command. This destructive command
   * requires elevated keys. By restoring a backup you will delete all the
   * existing entities in your subscription.
   *
   * @param string $api
   *   The API key.
   * @param string $secret
   *   The (optional) string secret key.
   *
   * @command acquia:contenthub-restore
   * @aliases ach-restore,acquia-contenthub-restore
   *
   * @throws \Exception
   */
  public function contenthubRestore($api, $secret) {
    $warning_message = "Are you sure you want to RESTORE the latest backup taken after purging your Content Hub Subscription?\n" .
      "*************************************************************************************\n" .
      "PROCEED WITH CAUTION. THIS ACTION WILL ELIMINATE ALL EXISTING ENTITIES IN YOUR CONTENT HUB SUBSCRIPTION.\n" .
      "This restore command should only be used after an accidental purge event has taken place *and* completed. This will attempt to restore\n" .
      "from the last purge-generated backup. In the event this fails, you will need to republish your content to Content Hub.
    For more information, check https://docs.acquia.com/content-hub.\n" .
      "*************************************************************************************\n" .
      "Are you sure you want to proceed?\n";
    if ($this->io()->confirm($warning_message)) {
      if (!empty($api) && !empty($secret)) {
        $client = $this->resetConnection($this->clientFactory->getClient(), $api, $secret);
      }
      else {
        $client = $this->clientFactory->getClient();
      }

      // Execute the 'restore' command.
      if (!$client) {
        throw new \Exception(dt('Error trying to connect to the Content Hub. Make sure this site is registered to Content hub.'));
      }
      $response = $client->restore();

      if (isset($response['success']) && $response['success'] === TRUE) {
        $this->output()->writeln("Your Subscription is being restored. All clients who have registered to received webhooks will be notified with a reindex webhook when the restore process has been completed.\n");
      }
      else {
        throw new \Exception(dt("Error trying to restore your subscription from a backup copy. You might require elevated keys to perform this operation."));
      }
    }
  }

  /**
   * View Historic entity logs from Content Hub.
   *
   * @param string $api
   *   The API Key.
   * @param string $secret
   *   The Secret Key.
   * @param array $options
   *   The options.
   *
   * @throws \Exception
   *
   * @internal param array $request_options An associative array of options
   *   whose values come from cli, aliases, config, etc.
   *
   * @option query
   *   The Elastic Search Query to search for logs.
   * @option size
   *   The number of log entries to be listed.
   * @option from
   *   The offset to start listing the log entries (Useful for pagination).
   *
   * @command acquia:contenthub-logs
   * @field-labels
   *   timestamp: Timestamp
   *   type: Type
   *   client: Client ID
   *   entity_uuid: Entity UUID
   *   status: Status
   *   request_id: Request ID
   *   id: ID
   * @aliases ach-logs,acquia-contenthub-logs
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The rows of fields.
   */
  public function contenthubLogs($api, $secret, array $options = ['query' => NULL, 'size' => NULL, 'from' => NULL,]) { // @codingStandardsIgnoreLine.
    if (!empty($api) && !empty($secret)) {
      $client = $this->resetConnection($this->clientFactory->getClient(), $api, $secret);
    }
    else {
      $client = $this->clientFactory->getClient();
    }

    $request_options = [];
    // Obtaining the limit.
    $size = $options["size"];
    if (isset($size)) {
      $size = (int) $size;
      if ($size < 1 || $size > 1000) {
        throw new \Exception(dt("The size has to be an integer from 1 to 1000."));
      }
      else {
        $request_options['size'] = $size;
      }
    }

    // Obtaining the offset.
    $from = $options["from"];
    if (isset($from)) {
      if (!is_numeric($from)) {
        throw new \Exception(dt("The start offset has to be numeric starting from 0."));
      }
      else {
        $request_options['from'] = $from;
      }
    }

    // Obtaining the query.
    $query = $options["query"];
    $query = !empty($query) ? $query : '';

    // Execute the 'history' command.
    if (!$client) {
      throw new \Exception(dt('Error trying to connect to the Content Hub. Make sure this site is registered to Content hub.'));
    }
    $logs = $client->logs($query, $request_options);
    if ($logs) {
      $rows = [];
      if (isset($logs['hits']['hits'])) {
        foreach ($logs['hits']['hits'] as $log) {
          $rows[] = [
            'timestamp' => $log['_source']['timestamp'],
            'type' => strtoupper($log['_source']['type']),
            'client' => $log['_source']['client'],
            'entity_uuid' => $log['_source']['entity'],
            'status' => strtoupper($log['_source']['status']),
            'request_id' => $log['_source']['request_id'],
            'id' => $log['_source']['id'],
          ];
        }
      }

      // Sort results DESC by 'timestamp' before presenting.
      usort($rows, function ($a, $b) {
        return strcmp($b["timestamp"], $a["timestamp"]);
      });
      return new RowsOfFields($rows);
    }
    else {
      throw new \Exception(dt("Error trying to print the entity logs."));
    }
  }

  /**
   * Shows Elastic Search field mappings from Content Hub.
   *
   * @command acquia:contenthub-mapping
   * @aliases ach-mapping,acquia-contenthub-mapping
   *
   * @throws \Exception
   */
  public function contenthubMapping() {
    $client = $this->clientFactory->getClient();

    if (!$client) {
      throw new \Exception(dt('Error trying to connect to the Content Hub. Make sure this site is registered to Content hub.'));
    }
    $output = $client->mapping();

    if ($output) {
      $this->output()->writeln(print_r($output, TRUE));
    }
    else {
      throw new \Exception(dt("Error trying to print the elastic search field mappings."));
    }
  }

  /**
   * Regenerates the Shared Secret used for Webhook Verification.
   *
   * @command acquia:contenthub-regenerate-secret
   * @aliases ach-regsec,acquia-contenthub-regenerate-secret
   *
   * @throws \Exception
   */
  public function contenthubRegenerateSecret() {
    $client = $this->clientFactory->getClient();
    $warning_message = "Are you sure you want to REGENERATE your shared-secret in the Content Hub?\n" .
      "*************************************************************************************\n" .
      "PROCEED WITH CAUTION. THIS COULD POTENTIALLY LEAD TO HAVING SOME SITES OUT OF SYNC.\n" .
      "Make sure you have ALL your sites correctly configured to receive webhooks before attempting to do this.\n" .
      "For more information, check https://docs.acquia.com/content-hub/known-issues.\n" .
      "*************************************************************************************\n";
    if ($this->io()->confirm($warning_message)) {
      if (!$client) {
        throw new \Exception(dt('Error trying to connect to the Content Hub. Make sure this site is registered to Content hub.'));
      }
      $output = $client->regenerateSharedSecret();

      if ($output) {
        $this->output()->writeln("Your Shared Secret has been regenerated. All clients who have registered to received webhooks are being notified of this change.\n");
      }
      else {
        throw new \Exception(dt("Error trying to regenerate the shared-secret in your subscription. Try again later."));
      }
    }
  }

  /**
   * Updates the Shared Secret used for Webhook Verification.
   *
   * @command acquia:contenthub-update-secret
   * @aliases ach-upsec,acquia-contenthub-update-secret
   */
  public function contenthubUpdateSecret() {
    $client = $this->clientFactory->getClient();

    if (!$client) {
      throw new \Exception(dt('The Content Hub client is not connected so the shared secret can not be updated.'));
    }

    $remote = $client->getRemoteSettings();
    $provider = $this->clientFactory->getProvider();
    if (!empty($remote['shared_secret']) && $provider === 'core_config') {
      $config = $this->configFactory->getEditable('acquia_contenthub.admin_settings');
      $config->set('shared_secret', $remote['shared_secret']);
      $config->save();
      $this->output()->writeln(dt('The shared secret has been updated to: @secret', ['@secret' => $remote['shared_secret']]));
      return;
    }
    $this->output()->writeln(dt('The settings object is read only. Your remote shared secret is: @secret Please update your settings object if necessary.', ['@secret' => $remote['shared_secret']]));
  }

  /**
   * Connects a site with contenthub.
   *
   * @command acquia:contenthub-connect-site
   * @aliases ach-connect,acquia-contenthub-connect-site
   *
   * @option $hostname
   *   Content Hub API URL.
   * @default $hostname null
   *
   * @option $api_key
   *   Content Hub API Key.
   * @default $api_key null
   *
   * @option $secret_key
   *   Content Hub API Secret.
   * @default $secret_key null
   *
   * @option $client_name
   *   The client name for this site.
   * @default $client_name null
   *
   * @usage ach-connect
   *   hostname, api_key, secret_key, client_name will be requested.
   * @usage ach-connect --hostname=https://us-east-1.content-hub.acquia.com
   *   api_key, secret_key, client_name will be requested.
   * @usage ach-connect --hostname=https://us-east-1.content-hub.acquia.com --api_key=API_KEY
   *   --secret_key=SECRET_KEY --client_name=CLIENT_NAME Connects site with
   *   following credentials.
   *
   */
  public function contenthubConnectSite() {
    $options = $this->input()->getOptions();

    // TODO: Revisit initial connection logic with our event subscibers.
    $settings = $this->clientFactory->getSettings();
    $config_origin = $settings->getUuid();

    $provider = $this->clientFactory->getProvider();
    $disabled = $provider != 'core_config';
    if ($disabled) {
      $message = dt('Settings are being provided by @provider, and already connected.', ['@provider' => $provider]);
      $this->logger()->log(LogLevel::CANCEL, $message);
      return;
    }

    if (!empty($config_origin)) {
      $message = dt('Site is already connected to Content Hub. Skipping.');
      $this->logger()->log(LogLevel::CANCEL, $message);
      return;
    }

    $io = $this->io();
    $hostname = $options['hostname'] ?? $io->ask(
        dt('What is the Content Hub API URL?'),
        'https://us-east-1.content-hub.acquia.com'
      );
    $api_key = $options['api_key'] ?? $io->ask(
        dt('What is your Content Hub API Key?')
      );
    $secret_key = $options['secret_key'] ?? $io->ask(
        dt('What is your Content Hub API Secret?')
      );
    $client_uuid = \Drupal::service('uuid')->generate();
    $client_name = $options['client_name'] ?? $io->ask(
        dt('What is the client name for this site?'),
        $client_uuid
      );

    $form_state = (new FormState())->setValues([
      'hostname' => $hostname,
      'api_key' => $api_key,
      'secret_key' => $secret_key,
      'client_name' => sprintf("%s_%s", $client_name, $client_uuid),
      'op' => t('Save configuration'),
    ]);

    // @TODO Errors handling can be improved after relocation of registration
    // logic into separate service.
    \Drupal::formBuilder()->submitForm(ContentHubSettingsForm::class, $form_state);
  }

  /**
   * Disconnects a site with contenthub.
   *
   * @command acquia:contenthub-disconnect-site
   * @aliases ach-disconnect,acquia-contenthub-disconnect-site
   */
  public function contenthubDisconnectSite() {
    $client = $this->clientFactory->getClient();

    if (!$client instanceof ContentHubClient) {
      $message = "Couldn't instantiate client. Please check connection settings.";
      $this->logger->log(LogLevel::CANCEL, $message);
      return;
    }

    $provider = $this->clientFactory->getProvider();
    $disabled = $provider != 'core_config';
    if ($disabled) {
      $message = dt(
        'Settings are being provided by %provider and cannot be disconnected manually.',
        ['%provider' => $provider]
      );
      $this->logger->log(LogLevel::CANCEL, $message);
      return;
    }

    try {
      $client->deleteClient();
    }
    catch (\Exception $exception) {
      $this->logger->log(LogLevel::ERROR, $exception->getMessage());
    }

    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('acquia_contenthub.admin_settings');
    $client_name = $config->get('client_name');
    $config->delete();

    // TODO: We should disconnect the webhook, but first we need to know its
    // ours.
    $message = dt(
      'Successfully disconnected site %site from contenthub',
      ['%site' => $client_name]
    );
    $this->logger->log(LogLevel::SUCCESS, $message);
  }

  /**
   * Perform a webhook management operation.
   *
   * @command acquia:contenthub-webhooks
   * @aliases ach-wh,acquia-contenthub-webhooks
   *
   * @param string $op
   *   The operation to use. Options are: register, unregister, list.
   *
   * @option webhook_url
   *   The webhook URL to register or unregister.
   * @default webhook_url null
   *
   * @usage acquia:contenthub-webhooks list
   *   Displays list of registered  webhooks.
   * @usage acquia:contenthub-webhooks register
   *   Registers new webhook. Current site url will be used.
   * @usage acquia:contenthub-webhooks register --webhook_url=http://example.com/acquia-contenthub/webhook
   *   Registers new webhook.
   * @usage acquia:contenthub-webhooks unregister
   *   Unregisters specified webhook. Current site url will be used.
   * @usage acquia:contenthub-webhooks unregister --webhook_url=http://example.com/acquia-contenthub/webhook
   *   Unregisters specified webhook.
   *
   * @throws \Exception
   */
  public function contenthubWebhooks($op) {
    $options = $this->input()->getOptions();

    $client = $this->clientFactory->getClient();
    if (!$client) {
      throw new \Exception(dt('The Content Hub client is not connected so the webhook operations could not be performed.'));
    }

    $webhook_url = $options['webhook_url'];
    if (empty($webhook_url)) {
      $webhook_url = Url::fromUri('internal:/acquia-contenthub/webhook', ['absolute' => TRUE])->toString();
    }

    switch ($op) {
      case 'register':
        $response = $client->addWebhook($webhook_url);
        if (empty($response)) {
          return;
        }
        if (isset($response['success']) && FALSE === $response['success']) {
          $message = dt('Registering webhooks encountered an error (code @code). @reason', [
            '@code' => $response['error']['code'],
            '@reason' => $response['error']['message'],
          ]);
          $this->logger->log(LogLevel::ERROR, $message);
          return;
        }

        $this->logger->log(LogLevel::SUCCESS, dt('Registered Content Hub Webhook: @url | @uuid', ['@url' => $webhook_url, '@uuid' => $response['uuid']]));
        break;

      case 'unregister':
        $webhooks = $client->getWebHooks();
        if (empty($webhooks)) {
          $this->logger->log(LogLevel::CANCEL, dt('You have no webhooks.'));
          return;
        }

        $webhook = $client->getWebHook($webhook_url);
        if (empty($webhook)) {
          $this->logger->log(LogLevel::CANCEL, dt('Webhook @url not found', ['@url' => $webhook_url]));
          return;
        }

        $success = $client->deleteWebhook($webhook['uuid']);
        if (!$success) {
          $this->logger->log(LogLevel::CANCEL, dt('There was an error unregistering the URL: @url', ['@url' => $webhook_url]));
          return;
        }

        $this->logger->log(LogLevel::SUCCESS, dt('Successfully unregistered Content Hub Webhook: @url', ['@url' => $webhook_url]));
        break;

      case 'list':
        $webhooks = $client->getWebHooks();
        if (empty($webhooks)) {
          $this->logger->warning(dt('You have no webhooks.'));
          return;
        }

        $rows_mapper = function ($webhook, $index) {
          return [
            $index + 1,
            $webhook['url'],
            $webhook['uuid'],
          ];
        };
        $rows = array_map($rows_mapper, $webhooks, array_keys($webhooks));

        (new Table($this->output()))
          ->setHeaders(['#', 'URL', 'UUID'])
          ->setRows($rows)
          ->render();
        break;

      default:
        // Invalid operation.
        throw new \Exception(dt('The op "@op" is invalid', ['@op' => $op]));
    }
  }

  /**
   * Resets a connection to the client.
   *
   * Allowing drush to connect to CH via a different set of api keys/secrets.
   *
   * @param \Acquia\ContentHubClient\ContentHubClient $client
   *   Client.
   * @param string $api_key
   *   API key.
   * @param string $secret_key
   *   Secret key.
   *
   * @return \Acquia\ContentHubClient\ContentHubClient
   *   New client instance.
   */
  protected function resetConnection(ContentHubClient $client, $api_key, $secret_key) {
    $settings = $client->getSettings();
    $new_settings = new Settings($settings->getName(), $settings->getUuid(), $api_key, $secret_key, $settings->getUrl());
    // Find out the module version in use.
    $module_info = system_get_info('module', 'acquia_contenthub');
    $module_version = (isset($module_info['version'])) ? $module_info['version'] : '0.0.0';
    $drupal_version = (isset($module_info['core'])) ? $module_info['core'] : '0.0.0';
    $client_user_agent = 'AcquiaContentHub/' . $drupal_version . '-' . $module_version;

    // Override configuration.
    $config = [
      'base_url' => $settings->getUrl(),
      'client-user-agent' => $client_user_agent,
    ];

    $dispatcher = \Drupal::service('event_dispatcher');
    return new ContentHubClient($config, $this->logger(), $new_settings, $new_settings->getMiddleware(), $dispatcher);
  }

}
