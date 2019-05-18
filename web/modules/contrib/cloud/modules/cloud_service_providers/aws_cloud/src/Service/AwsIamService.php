<?php

namespace Drupal\aws_cloud\Service;

use Aws\Iam\IamClient;
use Aws\Iam\Exception\IamException;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sts\StsClient;
use Aws\Credentials\AssumeRoleCredentialProvider;
use Aws\Credentials\CredentialProvider;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;

/**
 * AwsIamService service interacts with the AWS IAM api.
 */
class AwsIamService implements AwsIamServiceInterface {

  use StringTranslationTrait;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  private $messenger;

  /**
   * Cloud context string.
   *
   * @var string
   */
  private $cloudContext;

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
  private $cloudConfigPluginManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * TRUE to run the operation, FALSE to run the operation in validation mode.
   *
   * @var bool
   */
  private $dryRun;

  /**
   * Constructs a new AwsIamService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    Messenger $messenger,
    TranslationInterface $string_translation,
    CloudConfigPluginManagerInterface $cloud_config_plugin_manager
  ) {
    // Setup the logger.
    $this->logger = $logger_factory->get('aws_iam_service');

    // Setup the configuration factory.
    $this->configFactory = $config_factory;

    // Setup the dryRun flag.
    $this->dryRun = (bool) $this->configFactory->get('aws_cloud.settings')->get('aws_cloud_test_mode');

    // Setup the messenger.
    $this->messenger = $messenger;

    // Setup the $this->t()
    $this->stringTranslation = $string_translation;

    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudContext($cloud_context) {
    $this->cloudContext = $cloud_context;
    $this->cloudConfigPluginManager->setCloudContext($cloud_context);
  }

  /**
   * {@inheritdoc}
   */
  public function listInstanceProfiles(array $params = []) {
    $results = $this->execute('ListInstanceProfiles', $params);
    return $results;
  }

  /**
   * Execute the API of AWS IAM Service.
   *
   * @param string $operation
   *   The operation to perform.
   * @param array $params
   *   An array of parameters.
   *
   * @return array
   *   Array of execution result or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsIamServiceException
   *   If the IAM Client is null.
   */
  private function execute($operation, array $params = []) {
    $results = NULL;

    $iam_client = $this->getIamClient();
    if ($iam_client == NULL) {
      throw new AwsIamServiceException('No Iam Client found. Cannot perform API operations');
    }

    try {
      // Let other modules alter the parameters
      // before they are sent through the API.
      \Drupal::moduleHandler()->invokeAll('aws_cloud_pre_execute_alter', [
        &$params,
        $operation,
        $this->cloudContext,
      ]);

      $command = $iam_client->getCommand($operation, $params);
      $results = $iam_client->execute($command);

      // Let other modules alter the results before the module processes it.
      \Drupal::moduleHandler()->invokeAll('aws_cloud_post_execute_alter', [
        &$results,
        $operation,
        $this->cloudContext,
      ]);
    }
    catch (IamException $e) {
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
   * Load and return an IamClient.
   */
  private function getIamClient() {
    // Use the plugin manager to load the aws credentials.
    $credentials = $this->cloudConfigPluginManager->loadCredentials();
    $credentials['endpoint'] = 'https://iam.amazonaws.com';
    $credentials['region'] = 'us-east-1';

    try {
      $iam_params = [
        'region' => $credentials['region'],
        'version' => $credentials['version'],
      ];

      if ($credentials['use_instance_credentials'] == FALSE) {
        $provider = CredentialProvider::ini('default', $credentials['ini_file']);
        $provider = CredentialProvider::memoize($provider);

        if ($credentials['assume_role'] == TRUE) {
          // Assume role.
          $sts_params = [
            'region' => $credentials['region'],
            'version' => $credentials['version'],
            'credentials' => $provider,
          ];

          $assumeRoleCredentials = new AssumeRoleCredentialProvider([
            'client' => new StsClient($sts_params),
            'assume_role_params' => [
              'RoleArn' => $credentials['role_arn'],
              'RoleSessionName' => 'iam_client_assume_role',
            ],
          ]);

          // Memoize takes care of re-authenticating when the tokens expire.
          $assumeRoleCredentials = CredentialProvider::memoize($assumeRoleCredentials);
          $iam_params = [
            'region' => $credentials['region'],
            'version' => $credentials['version'],
            'credentials' => $assumeRoleCredentials,
          ];
        }
        else {
          $iam_params['credentials'] = $provider;
        }
      }
      $iam_client = new IamClient($iam_params);
    }
    catch (\Exception $e) {
      $iam_client = NULL;
      $this->logger->error($e->getMessage());
    }
    $this->addMockHandler($iam_client);
    return $iam_client;
  }

  /**
   * Add a mock handler of aws sdk for testing.
   *
   * The mock data of aws response is saved
   * in configuration "aws_cloud_mock_data".
   *
   * @param \Aws\Iam\IamClient $iam_client
   *   The IAM client.
   *
   * @see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_handlers-and-middleware.html
   */
  private function addMockHandler(IamClient $iam_client) {
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
          return new Result($result_data);
        }
        else {
          return new Result();
        }
      };

      // Set mock handler.
      $iam_client->getHandlerList()->setHandler(new MockHandler([$func, $func]));
    }
  }

}
