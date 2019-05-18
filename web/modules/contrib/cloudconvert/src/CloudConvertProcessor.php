<?php

namespace Drupal\cloudconvert;

use Drupal\cloudconvert\Entity\CloudConvertTaskInterface;
use Drupal\cloudconvert\Entity\CloudConvertTaskTypeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CloudConvertProcessor.
 *
 * @package Drupal\cloudconvert
 */
class CloudConvertProcessor implements ContainerInjectionInterface, CloudConvertProcessorInterface {

  /**
   * Cloud Convert API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CloudConvert API.
   *
   * @var \Drupal\cloudconvert\Api
   */
  protected $cloudConvertApi;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactory $configFactory, QueueFactory $queueFactory) {
    $this->apiKey = $configFactory->get('cloudconvert.settings')
      ->get('api_key');
    $httpClient = new Client([
      'curl' => [
        CURLOPT_TIMEOUT => 0,
        CURLOPT_TIMEOUT_MS => 0,
        CURLOPT_CONNECTTIMEOUT => 0,
      ],
    ]);
    $this->entityTypeManager = $entityTypeManager;
    $this->cloudConvertApi = new Api($this->apiKey, $httpClient);
    $this->configFactory = $configFactory;
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCloudConvertApi() {
    return $this->cloudConvertApi;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createProcess(CloudConvertTaskInterface $cloudConvertTask, Parameters $parameters) {
    $process = $this->cloudConvertApi->createProcess($parameters);
    $cloudConvertTask->setProcessId($process->getProcessId());
    $cloudConvertTask->setProcessInfo($process->getData());
    $cloudConvertTask->updateProcessParameters($parameters->getParameters());
    $cloudConvertTask->setStep($process->get('step'));

    return $process;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function startProcess(CloudConvertTaskInterface $cloudConvertTask) {
    $process = $this->getProcess($cloudConvertTask);
    /** @var \Drupal\file\FileInterface $file */
    $file = $cloudConvertTask->getOriginalFile();
    $parameters = new Parameters($cloudConvertTask->getProcessParameters());
    $process->start($parameters);
    $cloudConvertTask->setStep($process->get('step'));
    $cloudConvertTask->setProcessInfo($process->getData());
    $process->upload(fopen($file->getFileUri(), 'r'), $file->getFilename());

    return $process;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getProcess(CloudConvertTaskInterface $cloudConvertTask) {
    $processInfo = $cloudConvertTask->getProcessInfo();
    $parameters = new Parameters();
    return $this->cloudConvertApi->getProcess($processInfo['url'], $parameters);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function finishProcess(CloudConvertTaskInterface $cloudConvertTask) {
    $process = $this->getProcess($cloudConvertTask);
    $process->delete();
    $cloudConvertTask->delete();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function createTask(CloudConvertTaskTypeInterface $cloudConvertTaskType, FileInterface $file) {
    $cloudConvertTaskStorage = $this->entityTypeManager->getStorage('cloudconvert_task');
    $cloudConvertTask = $cloudConvertTaskStorage->create([
      'original_file_id' => $file->id(),
      'type' => $cloudConvertTaskType->id(),
      'process_info' => [],
      'process_parameters' => [],
      'step' => 'to-do',
    ]);

    return $cloudConvertTask;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   */
  public function downloadFile(Process $process) {
    $output = $process->get('output');

    $replace = FILE_EXISTS_RENAME;
    $baseDestination = 'temporary://';
    $destinationUri = file_destination($baseDestination . $output->filename, $replace);

    $process->downloadFile($destinationUri, $output->url . '/' . $output->filename);

    return $destinationUri;
  }

  /**
   * {@inheritdoc}
   */
  public function gatherInfo(Process $process) {
    return $process->get('info');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function createStartQueueItem(CloudConvertTaskInterface $cloudConvertTask, Parameters $parameters) {
    $this->createQueueItem($cloudConvertTask, 'cloudconvert_start_processor', $parameters->getParameters());
  }

  /**
   * Create a Queue Item.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   * @param string $queueName
   *   Queue.
   * @param array $parameters
   *   List of parameters.
   */
  private function createQueueItem(CloudConvertTaskInterface $cloudConvertTask, $queueName, array $parameters = []) {
    /** @var \Drupal\Core\Queue\QueueInterface $queueName */
    $queue = $this->queueFactory->get($queueName);
    $item = new \stdClass();
    $item->cloudconvert_task_id = $cloudConvertTask->id();

    $item->parameters = $parameters;

    $queue->createItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function createFinishQueueItem(CloudConvertTaskInterface $cloudConvertTask) {
    $this->createQueueItem($cloudConvertTask, 'cloudconvert_finish_processor');
  }

  /**
   * {@inheritdoc}
   */
  public function getCallbackUrl(CloudConvertTaskInterface $cloudConvertTask) {
    return Url::fromRoute('cloudconvert.callback', ['cloudconvert_task' => $cloudConvertTask->id()], ['absolute' => TRUE]);
  }

}
