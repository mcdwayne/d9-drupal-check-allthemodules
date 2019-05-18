<?php

namespace Drupal\aws_cloud\Service;

use Aws\Api\DateTimeResult;
use Aws\Credentials\AssumeRoleCredentialProvider;
use Aws\Credentials\CredentialProvider;
use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;
use Aws\Endpoint\EndpointProvider;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sts\StsClient;
use Drupal\aws_cloud\Entity\Ec2\SecurityGroup;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * AwsEc2Service service interacts with the AWS EC2 api.
 */
class AwsEc2Service implements AwsEc2ServiceInterface {

  use StringTranslationTrait;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cloud context string.
   *
   * @var string
   */
  private $cloudContext;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CloudConfigPlugin.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Entity field manager interface.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * TRUE to run the operation.  FALSE to run the operation in validation mode.
   *
   * @var bool
   */
  private $dryRun;

  /**
   * The lock interface.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  private $lock;

  /**
   * Constructs a new AwsEc2Service object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager instance.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   * @param \Drupal\core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock interface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, Messenger $messenger, TranslationInterface $string_translation, AccountInterface $current_user, CloudConfigPluginManagerInterface $cloud_config_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, EntityFieldManagerInterface $entity_field_manager, LockBackendInterface $lock) {
    // Setup the entity type manager for querying entities.
    $this->entityTypeManager = $entity_type_manager;
    // Setup the logger.
    $this->logger = $logger_factory->get('aws_ec2_service');
    // Setup the configuration factory.
    $this->configFactory = $config_factory;

    // Setup the dryRun flag.
    $this->dryRun = (bool) $this->configFactory->get('aws_cloud.settings')->get('aws_cloud_test_mode');

    // Setup the messenger.
    $this->messenger = $messenger;
    // Setup the $this->t()
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;

    $this->entityFieldManager = $entity_field_manager;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudContext($cloud_context) {
    $this->cloudContext = $cloud_context;
    $this->cloudConfigPluginManager->setCloudContext($cloud_context);
  }

  /**
   * Load and return an Ec2Client.
   */
  private function getEc2Client() {
    $credentials = $this->cloudConfigPluginManager->loadCredentials();
    try {
      $ec2_params = [
        'region' => $credentials['region'],
        'version' => $credentials['version'],
      ];
      if ($credentials['use_instance_credentials'] == FALSE) {
        // Assume role using credential ini file.
        $provider = CredentialProvider::ini('default', $credentials['ini_file']);
        $provider = CredentialProvider::memoize($provider);

        if ($credentials['assume_role'] == TRUE) {

          $sts_params = [
            'region' => $credentials['region'],
            'version' => $credentials['version'],
            'credentials' => $provider,
          ];

          $assumeRoleCredentials = new AssumeRoleCredentialProvider([
            'client' => new StsClient($sts_params),
            'assume_role_params' => [
              'RoleArn' => $credentials['role_arn'],
              'RoleSessionName' => 'ec2_client_assume_role',
            ],
          ]);

          // Memoize takes care of re-authenticating when the tokens expire.
          $assumeRoleCredentials = CredentialProvider::memoize($assumeRoleCredentials);
          $ec2_params['credentials'] = $assumeRoleCredentials;
        }
        else {
          $ec2_params['credentials'] = $provider;
        }
      }
      $ec2_client = new Ec2Client($ec2_params);
    }
    catch (\Exception $e) {
      $ec2_client = NULL;
      $this->logger->error($e->getMessage());
    }
    $this->addMockHandler($ec2_client);
    return $ec2_client;
  }

  /**
   * Add a mock handler of aws sdk for testing.
   *
   * The mock data of aws response is saved
   * in configuration "aws_cloud_mock_data".
   *
   * @param \Aws\Ec2\Ec2Client $ec2_client
   *   The ec2 client.
   *
   * @see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_handlers-and-middleware.html
   */
  private function addMockHandler(Ec2Client $ec2_client) {
    $mock_data = $this->configFactory->get('aws_cloud.settings')->get('aws_cloud_mock_data');
    if ($this->dryRun && $mock_data) {
      $func = function ($command, $request) {
        $mock_data = \Drupal::service('config.factory')
          ->get('aws_cloud.settings')
          ->get('aws_cloud_mock_data');
        $mock_data = json_decode($mock_data, TRUE);

        // If the mock data of a command is defined.
        $command_name = $command->getName();
        if (isset($mock_data[$command_name])) {
          $result_data = $mock_data[$command_name];

          // Because launch time is special,
          // we need to convert it from string to DateTimeResult.
          if ($command_name == 'DescribeInstances') {
            foreach ($result_data['Reservations'] as &$reservation) {
              foreach ($reservation['Instances'] as &$instance) {
                if (!empty($instance['LaunchTime'])) {
                  $instance['LaunchTime'] = new DateTimeResult($instance['LaunchTime']);
                }
              }
            }
          }

          return new Result($result_data);
        }
        elseif ($command_name == 'DescribeAccountAttributes') {
          // Return an empty array so testing doesn't error out.
          $result_data = [
            'AccountAttributes' => [
              [
                'AttributeName' => 'supported-platforms',
                'AttributeValues' => [
                  [
                    'AttributeValue' => 'VPC',
                  ],
                ],
              ],
            ],
          ];
          return new Result($result_data);
        }
        else {
          return new Result();
        }
      };

      // Set mock handler.
      $ec2_client->getHandlerList()->setHandler(new MockHandler([$func, $func]));
    }
  }

  /**
   * Execute the API of AWS EC2 Service.
   *
   * @param string $operation
   *   The operation to perform.
   * @param array $params
   *   An array of parameters.
   *
   * @return array
   *   Array of execution result or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  private function execute($operation, array $params = []) {
    $results = NULL;

    if (empty($params)) {
      throw new AwsEc2ServiceException(sprintf("No parameters passed for operation %s", ['%s' => $operation]));
    }

    $ec2_client = $this->getEc2Client();
    if ($ec2_client == NULL) {
      throw new AwsEc2ServiceException('No Ec2 Client found.  Cannot perform API operations');
    }

    try {
      // Let other modules alter the parameters
      // before they are sent through the API.
      \Drupal::moduleHandler()->invokeAll('aws_cloud_pre_execute_alter', [
        &$params,
        $operation,
        $this->cloudContext,
      ]);

      $command = $ec2_client->getCommand($operation, $params);
      $results = $ec2_client->execute($command);

      // Let other modules alter the results before the module processes it.
      \Drupal::moduleHandler()->invokeAll('aws_cloud_post_execute_alter', [
        &$results,
        $operation,
        $this->cloudContext,
      ]);
    }
    catch (Ec2Exception $e) {
      $this->messenger->addError($this->t('Error: The operation "@operation" could not be performed.', [
        '@operation' => $operation,
      ]));

      $this->messenger->addError($this->t('Error Info: @error_info', [
        '@error_info' => $e->getAwsErrorCode(),
      ]));

      $this->messenger->addError($this->t('Error from: @error_type-side', [
        '@error_type' => $e->getAwsErrorType(),
      ]));

      $this->messenger->addError($this->t('Status Code: @status_code', [
        '@status_code' => $e->getStatusCode(),
      ]));

      $this->messenger->addError($this->t('Message: @msg', ['@msg' => $e->getAwsErrorMessage()]));

    }
    catch (\InvalidArgumentException $e) {
      $this->messenger->addError($e->getMessage());
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function associateAddress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('AssociateAddress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function allocateAddress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('AllocateAddress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function associateIamInstanceProfile(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('AssociateIamInstanceProfile', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeAccountAttributes(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeAccountAttributes', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function disassociateIamInstanceProfile(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DisassociateIamInstanceProfile', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceIamInstanceProfileAssociation(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('ReplaceIamInstanceProfileAssociation', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeIamInstanceProfileAssociations(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeIamInstanceProfileAssociations', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function authorizeSecurityGroupIngress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('AuthorizeSecurityGroupIngress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function authorizeSecurityGroupEgress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('AuthorizeSecurityGroupEgress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createImage(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateImage', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createKeyPair(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateKeyPair', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createNetworkInterface(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateNetworkInterface', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createTags(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateTags', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTags(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeleteTags', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createVolume(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateVolume', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createSnapshot(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateSnapshot', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function createSecurityGroup(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('CreateSecurityGroup', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deregisterImage(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeregisterImage', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeInstances(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeInstances', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeInstanceAttribute(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeInstanceAttribute', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeImages(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeImages', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeSecurityGroups(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeSecurityGroups', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeNetworkInterfaces(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeNetworkInterfaces', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeAddresses(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeAddresses', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeSnapshots(array $params = []) {
    $params += $this->getDefaultParameters();
    $params['RestorableByUserIds'] = [$this->cloudConfigPluginManager->loadConfigEntity()->get('field_account_id')->value];
    $results = $this->execute('DescribeSnapshots', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeKeyPairs(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeKeyPairs', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeVolumes(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeVolumes', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeAvailabilityZones(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeAvailabilityZones', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeVpcs(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeVpcs', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function describeSubnets(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DescribeSubnets', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions() {
    $regions = EndpointProvider::defaultProvider()
      ->getPartition($region = '', 'ec2')['regions'];

    foreach ($regions as $region => $region_name) {
      $item[$region] = $region_name['description'];
    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointUrls() {
    // The $endpoints will be an array like ['us-east-1' => [], 'us-east-2' =>
    // [], ...].
    $endpoints = EndpointProvider::defaultProvider()
      ->getPartition('', 'ec2')['services']['ec2']['endpoints'];

    $urls = [];
    foreach ($endpoints as $endpoint => $item) {
      $url = "https://ec2.$endpoint.amazonaws.com";
      $urls[$endpoint] = $url;
    }

    return $urls;
  }

  /**
   * {@inheritdoc}
   */
  public function importKeyPair(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('ImportKeyPair', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function terminateInstance(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('TerminateInstances', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSecurityGroup(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeleteSecurityGroup', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteNetworkInterface(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeleteNetworkInterface', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function disassociateAddress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DisassociateAddress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAddress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('ReleaseAddress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyPair(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeleteKeyPair', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteVolume(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeleteVolume', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function attachVolume(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('AttachVolume', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function detachVolume(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DetachVolume', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSnapshot(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('DeleteSnapshot', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function revokeSecurityGroupIngress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('RevokeSecurityGroupIngress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function revokeSecurityGroupEgress(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('RevokeSecurityGroupEgress', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function runInstances(array $params = [], array $tags = []) {
    $params += $this->getDefaultParameters();

    // Add meta tags to identify where the instance was launched from.
    $params['TagSpecifications'] = [
      [
        'ResourceType' => 'instance',
        'Tags' => [
          [
            'Key' => 'cloud_launch_origin',
            'Value' => \Drupal::request()->getHost(),
          ],
          [
            'Key' => 'cloud_launch_software',
            'Value' => 'Drupal 8 Cloud Orchestrator',
          ],
          [
            'Key' => 'cloud_launched_by',
            'Value' => $this->currentUser->getAccountName(),
          ],
          [
            'Key' => 'cloud_launched_by_uid',
            'Value' => $this->currentUser->id(),
          ],
        ],
      ],
    ];

    // If there are tags, add them to the Tags array.
    foreach ($tags as $tag) {
      $params['TagSpecifications'][0]['Tags'][] = $tag;
    }

    $results = $this->execute('RunInstances', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function stopInstances(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('StopInstances', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function startInstances(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('StartInstances', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyInstanceAttribute(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('ModifyInstanceAttribute', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function rebootInstances(array $params = []) {
    $params += $this->getDefaultParameters();
    $results = $this->execute('RebootInstances', $params);
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function updateInstances(array $params = [], $clear = TRUE) {
    $updated = FALSE;
    $entity_type = 'aws_cloud_instance';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    // Call the api and get all instances.
    $result = $this->describeInstances($params);
    if ($result != NULL) {
      $all_instances = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the instances by setting up
      // the array with the instance_id.
      foreach ($all_instances as $instance) {
        $stale[$instance->getInstanceId()] = $instance;
      }

      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Instance Update');
      // Get instance types.
      $instance_types = aws_cloud_get_instance_types($this->cloudContext);

      // Loop through the reservations and store each one as an Instance entity.
      foreach ($result['Reservations'] as $reservation) {

        foreach ($reservation['Instances'] as $instance) {
          // Keep track of instances that do not exist anymore
          // delete them after saving the rest of the instances.
          if (isset($stale[$instance['InstanceId']])) {
            unset($stale[$instance['InstanceId']]);
          }
          // Store the Reservation OwnerId in instance so batch
          // callback has access.
          $instance['reservation_ownerid'] = $reservation['OwnerId'];
          $instance['reservation_id'] = $reservation['ReservationId'];

          $batch_builder->addOperation([
            '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
            'updateInstance',
          ], [$this->cloudContext, $instance]);

        }
      }
      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, $clear]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }
    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function updateImages(array $params = [], $clear = FALSE) {
    $updated = FALSE;
    $entity_type = 'aws_cloud_image';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    // Load all entities by cloud_context.
    $image_entities = $this->entityTypeManager->getStorage($entity_type)->loadByProperties(
      ['cloud_context' => $this->cloudContext]
    );
    $result = $this->describeImages($params);

    if ($result != NULL) {
      $stale = [];
      // Make it easier to lookup the images by setting up
      // the array with the image_id.
      foreach ($image_entities as $image) {
        $stale[$image->getImageId()] = $image;
      }

      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Image Update');
      foreach ($result['Images'] as $image) {
        // Keep track of images that do not exist anymore
        // delete them after saving the rest of the images.
        if (isset($stale[$image['ImageId']])) {
          unset($stale[$image['ImageId']]);
        }
        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateImage',
        ], [$this->cloudContext, $image]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, $clear]);
      $this->runBatch($batch_builder);
      $updated = count($result['Images']);
    }
    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function updateSecurityGroups(array $params = [], $clear = TRUE) {
    $updated = FALSE;
    $entity_type = 'aws_cloud_security_group';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    $result = $this->describeSecurityGroups($params);
    if ($result != NULL) {
      $all_groups = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the groups by setting up
      // the array with the group_id.
      foreach ($all_groups as $group) {
        $stale[$group->getGroupId()] = $group;
      }

      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Security Group Update');
      foreach ($result['SecurityGroups'] as $security_group) {

        // Keep track of instances that do not exist anymore
        // delete them after saving the rest of the instances.
        if (isset($stale[$security_group['GroupId']])) {
          unset($stale[$security_group['GroupId']]);
        }
        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateSecurityGroup',
        ], [$this->cloudContext, $security_group]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, $clear]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }

    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function setupIpPermissions(SecurityGroup &$security_group, $field, array $ec2_permissions) {
    // Permissions are always overwritten with the latest from
    // EC2.  The reason is that there is no way to guarantee a 1
    // to 1 mapping from the $security_group['IpPermissions'] array.
    // There is no ip permission id coming back from EC2.
    // Clear out all items before re-adding them.
    $i = 0;
    while ($i <= $security_group->$field->count()) {
      if ($security_group->$field->get($i)) {
        $security_group->$field->removeItem($i);
      }
      $i++;
    }

    // Setup all permission objects.
    $count = 0;
    foreach ($ec2_permissions as $permissions) {
      $permission_objects = $this->setupIpPermissionObject($permissions);
      // Loop through the permission objects and add them to the
      // security group ip_permission field.
      foreach ($permission_objects as $permission) {
        $security_group->$field->set($count, $permission);
        $count++;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setupIpPermissionObject(array $ec2_permission) {
    $ip_permissions = [];

    // Get the field definition for an IpPermission object.
    $definition = $this->entityFieldManager->getBaseFieldDefinitions('aws_cloud_security_group');

    // Setup the more global attributes.
    $from_port = isset($ec2_permission['FromPort']) ? "{$ec2_permission['FromPort']}" : NULL;
    $to_port = isset($ec2_permission['ToPort']) ? "{$ec2_permission['ToPort']}" : NULL;
    $ip_protocol = $ec2_permission['IpProtocol'];

    // To keep things consistent, if ip_protocol is -1,
    // set from_port and to_port as 0-65535.
    if ($ip_protocol == -1) {
      $from_port = "0";
      $to_port = "65535";
    }

    if (isset($ec2_permission['IpRanges']) && count($ec2_permission['IpRanges'])) {
      // Create a IPv4 permission object.
      foreach ($ec2_permission['IpRanges'] as $ip_range) {
        $ip_range_permission = $this->fieldTypePluginManager->createInstance('ip_permission', [
          'field_definition' => $definition['ip_permission'],
          'parent' => NULL,
          'name' => NULL,
        ]);
        // Source is an internal identifier.  Doesn't come from EC2.
        $ip_range_permission->source = 'ip4';
        $ip_range_permission->cidr_ip = $ip_range['CidrIp'];

        $ip_range_permission->from_port = $from_port;
        $ip_range_permission->to_port = $to_port;
        $ip_range_permission->ip_protocol = $ip_protocol;

        $ip_permissions[] = $ip_range_permission;
      }
    }

    if (isset($ec2_permission['Ipv6Ranges']) && count($ec2_permission['Ipv6Ranges'])) {
      // Create IPv6 permissions object.
      foreach ($ec2_permission['Ipv6Ranges'] as $ip_range) {
        $ip_v6_permission = $this->fieldTypePluginManager->createInstance('ip_permission', [
          'field_definition' => $definition['ip_permission'],
          'parent' => NULL,
          'name' => NULL,
        ]);
        // Source is an internal identifier.  Doesn't come from EC2.
        $ip_v6_permission->source = 'ip6';
        $ip_v6_permission->cidr_ip_v6 = $ip_range['CidrIpv6'];
        $ip_v6_permission->from_port = $from_port;
        $ip_v6_permission->to_port = $to_port;
        $ip_v6_permission->ip_protocol = $ip_protocol;

        $ip_permissions[] = $ip_v6_permission;
      }
    }

    if (isset($ec2_permission['UserIdGroupPairs']) && count($ec2_permission['UserIdGroupPairs'])) {
      // Create Group permissions object.
      foreach ($ec2_permission['UserIdGroupPairs'] as $group) {
        $group_permission = $this->fieldTypePluginManager->createInstance('ip_permission', [
          'field_definition' => $definition['ip_permission'],
          'parent' => NULL,
          'name' => NULL,
        ]);
        // Source is an internal identifier.  Doesn't come from EC2.
        $group_permission->source = 'group';
        $group_permission->group_id = isset($group['GroupId']) ? $group['GroupId'] : NULL;
        $group_permission->group_name = isset($group['GroupName']) ? $group['GroupName'] : NULL;
        $group_permission->user_id = isset($group['UserId']) ? $group['UserId'] : NULL;
        $group_permission->peering_status = isset($group['PeeringStatus']) ? $group['PeeringStatus'] : NULL;
        $group_permission->vpc_id = isset($group['VpcId']) ? $group['VpcId'] : NULL;
        $group_permission->peering_connection_id = isset($group['VpcPeeringConnectionId']) ? $group['VpcPeeringConnectionId'] : NULL;

        $group_permission->from_port = $from_port;
        $group_permission->to_port = $to_port;
        $group_permission->ip_protocol = $ip_protocol;
        $ip_permissions[] = $group_permission;
      }
    }
    return $ip_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function updateNetworkInterfaces() {
    $updated = FALSE;
    $entity_type = 'aws_cloud_network_interface';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    $result = $this->describeNetworkInterfaces();
    if ($result != NULL) {
      $all_interfaces = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the groups by setting up
      // the array with the group_id.
      foreach ($all_interfaces as $interface) {
        $stale[$interface->getNetworkInterfaceId()] = $interface;
      }

      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Network Interface Update');
      foreach ($result['NetworkInterfaces'] as $network_interface) {
        // Keep track of network interfaces that do not exist anymore
        // delete them after saving the rest of the network interfaces.
        if (isset($stale[$network_interface['NetworkInterfaceId']])) {
          unset($stale[$network_interface['NetworkInterfaceId']]);
        }
        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateNetworkInterface',
        ], [$this->cloudContext, $network_interface]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, TRUE]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }
    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function updateElasticIp() {
    $updated = FALSE;
    $entity_type = 'aws_cloud_elastic_ip';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    $result = $this->describeAddresses();

    if ($result != NULL) {
      $all_ips = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the groups by setting up
      // the array with the group_id.
      foreach ($all_ips as $ip) {
        $stale[$ip->getPublicIp()] = $ip;
      }

      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('ElasticIp Update');
      foreach ($result['Addresses'] as $elastic_ip) {
        // Keep track of Ips that do not exist anymore
        // delete them after saving the rest of the Ips.
        if (isset($stale[$elastic_ip['PublicIp']])) {
          unset($stale[$elastic_ip['PublicIp']]);
        }
        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateElasticIp',
        ], [$this->cloudContext, $elastic_ip]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, TRUE]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }

    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function updateKeyPairs() {
    $updated = FALSE;
    $entity_type = 'aws_cloud_key_pair';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    $result = $this->describeKeyPairs();
    if ($result != NULL) {
      $all_keys = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the groups by setting up
      // the array with the group_id.
      foreach ($all_keys as $key) {
        $stale[$key->getKeyPairName()] = $key;
      }
      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Keypair Update');
      foreach ($result['KeyPairs'] as $key_pair) {
        // Keep track of key pair that do not exist anymore
        // delete them after saving the rest of the key pair.
        if (isset($stale[$key_pair['KeyName']])) {
          unset($stale[$key_pair['KeyName']]);
        }
        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateKeyPair',
        ], [$this->cloudContext, $key_pair]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, TRUE]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }

    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function updateVolumes() {
    $updated = FALSE;
    $entity_type = 'aws_cloud_volume';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    $result = $this->describeVolumes();

    if ($result != NULL) {
      $all_volumes = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the groups by setting up
      // the array with the group_id.
      foreach ($all_volumes as $volume) {
        $stale[$volume->getVolumeId()] = $volume;
      }
      $snapshot_id_name_map = $this->getSnapshotIdNameMap($result['Volumes']);

      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Volume Update');
      foreach ($result['Volumes'] as $volume) {
        // Keep track of network interfaces that do not exist anymore
        // delete them after saving the rest of the network interfaces.
        if (isset($stale[$volume['VolumeId']])) {
          unset($stale[$volume['VolumeId']]);
        }
        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateVolume',
        ], [$this->cloudContext, $volume, $snapshot_id_name_map]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, TRUE]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }

    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function updateSnapshots() {
    $updated = FALSE;
    $entity_type = 'aws_cloud_snapshot';
    $lock_name = $this->getLockKey($entity_type);

    if (!$this->lock->acquire($lock_name)) {
      return FALSE;
    }

    $result = $this->describeSnapshots();
    if ($result != NULL) {
      $all_snapshots = $this->loadAllEntities($entity_type);
      $stale = [];
      // Make it easier to lookup the snapshot by setting up
      // the array with the snapshot_id.
      foreach ($all_snapshots as $snapshot) {
        $stale[$snapshot->getSnapshotId()] = $snapshot;
      }
      /* @var \Drupal\Core\Batch\BatchBuilder $batch_builder */
      $batch_builder = $this->initBatch('Snapshot Update');
      foreach ($result['Snapshots'] as $snapshot) {
        // Keep track of snapshot that do not exist anymore
        // delete them after saving the rest of the snapshots.
        if (isset($stale[$snapshot['SnapshotId']])) {
          unset($stale[$snapshot['SnapshotId']]);
        }

        $batch_builder->addOperation([
          '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
          'updateSnapshot',
        ], [$this->cloudContext, $snapshot]);
      }

      $batch_builder->addOperation([
        '\Drupal\aws_cloud\Service\AwsCloudBatchOperations',
        'finished',
      ], [$entity_type, $stale, TRUE]);
      $this->runBatch($batch_builder);
      $updated = TRUE;
    }

    $this->lock->release($lock_name);
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function getVpcs() {
    $vpcs = [];
    $results = $this->describeVpcs();
    foreach (array_column($results['Vpcs'], 'VpcId') as $key => $vpc) {
      $vpcs[$vpc] = $results['Vpcs'][$key]['CidrBlock'] . " ($vpc)";
    }
    return $vpcs;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityZones() {
    $zones = [];
    $results = $this->describeAvailabilityZones();
    if ($results != NULL) {
      foreach (array_column($results['AvailabilityZones'], 'ZoneName') as $availability_zone) {
        $zones[$availability_zone] = $availability_zone;
      }
    }
    return $zones;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedPlatforms() {
    $platforms = [];
    $results = $this->describeAccountAttributes([
      'AttributeNames' => [
        'supported-platforms',
      ],
    ]);
    if ($results != NULL) {
      foreach ($results['AccountAttributes'] as $attribute) {
        if ($attribute['AttributeName'] == 'supported-platforms') {
          foreach ($attribute['AttributeValues'] as $value) {
            $platforms[] = $value['AttributeValue'];
          }
        }
      }
    }
    return $platforms;
  }

  /**
   * {@inheritdoc}
   */
  public function clearAllEntities() {
    $timestamp = $this->getTimestamp();
    $this->clearEntities('aws_cloud_instance', $timestamp);
    $this->clearEntities('aws_cloud_security_group', $timestamp);
    $this->clearEntities('aws_cloud_image', $timestamp);
    $this->clearEntities('aws_cloud_network_interface', $timestamp);
    $this->clearEntities('aws_cloud_elastic_ip', $timestamp);
    $this->clearEntities('aws_cloud_key_pair', $timestamp);
    $this->clearEntities('aws_cloud_volume', $timestamp);
    $this->clearEntities('aws_cloud_snapshot', $timestamp);
  }

  /**
   * Helper method to get the current timestamp.
   *
   * @return int
   *   The current timestamp.
   */
  private function getTimestamp() {
    return time();
  }

  /**
   * Setup the default parameters that all API calls will need.
   *
   * @return array
   *   Array of default parameters.
   */
  private function getDefaultParameters() {
    return [
      'DryRun' => $this->dryRun,
    ];
  }

  /**
   * Helper method to delete entities.
   *
   * @param string $entity_type
   *   Entity Type.
   * @param array $entity_ids
   *   Array of entity ids.
   */
  private function deleteEntities($entity_type, array $entity_ids) {
    $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_ids);
    $this->entityTypeManager->getStorage($entity_type)->delete($entities);
  }

  /**
   * Clear entities.
   *
   * @param string $entity_type
   *   Entity Type.
   * @param int $timestamp
   *   The timestamp for condition of refreshed time to clear entities.
   */
  private function clearEntities($entity_type, $timestamp) {
    $entity_ids = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->condition('refreshed', $timestamp, '<')
      ->condition('cloud_context', $this->cloudContext)
      ->execute();
    if (count($entity_ids)) {
      $this->deleteEntities($entity_type, $entity_ids);
    }
  }

  /**
   * Helper method to load an entity using parameters.
   *
   * @param string $entity_type
   *   Entity Type.
   * @param string $id_field
   *   Entity id field.
   * @param string $id_value
   *   Entity id value.
   *
   * @return int
   *   Entity id.
   */
  public function getEntityId($entity_type, $id_field, $id_value) {
    $entities = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->condition($id_field, $id_value)
      ->condition('cloud_context', $this->cloudContext)
      ->execute();
    return array_shift($entities);
  }

  /**
   * Helper method to load all entities of a given type.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entities.
   */
  private function loadAllEntities($entity_type) {
    return $this->entityTypeManager->getStorage($entity_type)->loadByProperties(
      ['cloud_context' => [$this->cloudContext]]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSnapshotIdNameMap(array $volumes) {
    $snapshot_ids = array_filter(array_column($volumes, 'SnapshotId'));
    if (empty($snapshot_ids)) {
      return [];
    }

    $map = [];
    foreach ($snapshot_ids as $snapshot_id) {
      $map[$snapshot_id] = '';
    }

    $result = $this->describeSnapshots();
    foreach ($result['Snapshots'] as $snapshot) {
      $snapshot_id = $snapshot['SnapshotId'];
      if (!array_key_exists($snapshot_id, $map)) {
        continue;
      }

      $map[$snapshot_id] = $this->getTagName($snapshot, '');
    }

    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getTagName(array $aws_obj, $default_value) {
    $name = $default_value;
    if (!isset($aws_obj['Tags'])) {
      return $name;
    }

    foreach ($aws_obj['Tags'] as $tag) {
      if ($tag['Key'] == 'Name') {
        $name = $tag['Value'];
        break;
      }
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateIps(array $network_interfaces) {
    $ip_string = FALSE;
    $private_ips = [];
    foreach ($network_interfaces as $interface) {
      $private_ips[] = $interface['PrivateIpAddress'];
    }
    if (count($private_ips)) {
      $ip_string = implode(', ', $private_ips);
    }
    return $ip_string;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateInstanceCost(array $instance, array $instance_types) {
    $cost = NULL;
    if ($instance['State']['Name'] == 'stopped') {
      return $cost;
    }

    $instance_type = $instance['InstanceType'];
    if (isset($instance_types[$instance_type])) {
      $parts = explode(':', $instance_types[$instance_type]);
      $hourly_rate = $parts[4];
      $launch_time = strtotime($instance['LaunchTime']->__toString());
      $cost = round((time() - $launch_time) / 3600 * $hourly_rate, 2);
    }
    return $cost;
  }

  /**
   * {@inheritdoc}
   */
  public function getUidTagValue(array $tags_array, $key) {
    $uid = 0;
    if (isset($tags_array['Tags'])) {
      foreach ($tags_array['Tags'] as $tag) {
        if ($tag['Key'] == $key) {
          $uid = $tag['Value'];
          break;
        }
      }
    }
    return $uid;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceUid($instance_id) {
    $uid = 0;
    $instance = $this->entityTypeManager
      ->getStorage('aws_cloud_instance')
      ->loadByProperties([
        'instance_id' => $instance_id,
      ]);

    if (count($instance) > 0) {
      $instance = array_shift($instance);
      $uid = $instance->getOwnerId();
    }
    return $uid;
  }

  /**
   * Initialize a new batch builder.
   *
   * @param string $batch_name
   *   The batch name.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The initialized batch object.
   */
  private function initBatch($batch_name) {
    return (new BatchBuilder())
      ->setTitle($batch_name);
  }

  /**
   * Run the batch job to process entities.
   *
   * @param \Drupal\Core\Batch\BatchBuilder $batch_builder
   *   The batch builder object.
   */
  private function runBatch(BatchBuilder $batch_builder) {
    // Log the start time.
    $start = $this->getTimestamp();
    $batch_array = $batch_builder->toArray();
    batch_set($batch_array);
    // Reset the progressive so batch works with out a web head.
    $batch = &batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
    // Log the end time.
    $end = $this->getTimestamp();
    $this->logger->info(
      $this->t('@updater - @cloud_context: Batch operation took @time seconds.',
        [
          '@cloud_context' => $this->cloudContext,
          '@updater' => $batch_array['title'],
          '@time' => $end - $start,
        ]));
  }

  /**
   * Generate a lock key based on entity name.
   *
   * @param string $name
   *   The entity name.
   *
   * @return string
   *   The lock key.
   */
  private function getLockKey($name) {
    return $this->cloudContext . '_' . $name;
  }

}
