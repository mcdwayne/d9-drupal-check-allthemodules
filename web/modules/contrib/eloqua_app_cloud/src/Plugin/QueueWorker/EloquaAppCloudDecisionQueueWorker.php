<?php

namespace Drupal\eloqua_app_cloud\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Psr\Log\LoggerInterface;


/**
 * Give it 60 seconds?
 *
 * @property  logger
 * @QueueWorker(
 *  id = "eloqua_app_cloud_decision_queue_worker",
 *  title = @Translation("The Eloqua AppCloud Queue worker for decisions."),
 *  cron = {"time" = 60},
 * )
 */
class EloquaAppCloudDecisionQueueWorker extends EloquaAppCloudQueueWorkerBase implements QueueWorkerInterface, ContainerFactoryPluginInterface {

  /**
   * @var ClientFactory
   */
  protected $eloquaClientFactory;

  /**
   * @var QueueFactory
   */
  protected $queueFactory;

  /**
   * @var  ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var  LoggerInterface
   */
  protected $logger;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration, $plugin_id, $plugin_definition, ClientFactory $eloqua_client_factory, QueueFactory $queueFactory, ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eloquaClientFactory = $eloqua_client_factory;
    $this->queueFactory = $queueFactory;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eloqua.client_factory'),
      $container->get('queue'),
      $container->get('config.factory'),
      $container->get('logger.channel.eloqua_app_cloud_queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($queueItem) {
    $instanceId = $queueItem->instanceId;
    $executionId = $queueItem->executionId;

    // The queue item may contain any number of contacts.
    // We can only send 5000 at a time. If there are more then that we need to requeue the remainder.
    // Either way, build a batch request and send it. Data items on the queue will contain an array
    // of possible records. Iterate over the records and see what we need to do.
    $records = $queueItem->records;

    // Splice off the first 5000 records.
    // If there are any left requeue them for the next run through.
    $chunk = array_splice($records, 0, 5000);

    if (count($records)) {
      // Requeue the remainder.
      $queue = $this->queueFactory->get($queueItem->queueWorker);
      // Overwrite the previous queue item with this reduced set.
      $queueItem->records = $records;
      $queue->createItem($queueItem);
    }
    $this->logger->debug("Queue execution:@exid run #@chunk records, requeue #@requeue records.",["@exid" => $executionId, "@chunk" => count($chunk), "@requeue" => count($records)]);

    // $api will be either 'contacts' or 'customObjects'.
    $api = $queueItem->api;
    $fieldList = $queueItem->fieldList;
    // Let's get a client so we can send our bulk API requests.
    $client = $this->eloquaClientFactory->get();
    $clientConfig = $this->configFactory->get('eloqua_rest_api.settings');
    // Eloqua is sometimes very slow to respond -- be wary of timeouts.
    $client->getHttpClient()->setOption('timeout', 90000);
    $client->authenticate(
      $clientConfig->get('eloqua_rest_api_site_name'),
      $clientConfig->get('eloqua_rest_api_login_name'),
      $clientConfig->get('eloqua_rest_api_login_password')
    );


    // @TODO how to really handle custom objects.
    // If $cdo_id is set, push the Id to bulk process.
    if (!empty($cdoId)) {
      $bulkApi = $client->api($api)->bulk($cdoId);
    }
    else {
      $bulkApi = $client->api($api)->bulk();
    }

    // Sets connector to 'import' mode.
    $bulkApi->imports();

    $mapping = [
      'name' => 'Decision results import',
      'identifierFieldName' => 'EmailAddress',
      'updateRule' => "always",
      'fields' => $fieldList,
    ];

    // Now run through the chunk we have and split into yeses and noes.
    $yes = [];
    $no = [];
    foreach ($chunk as $record) {
      $item = new \stdClass();
      $item->EmailAddress = $record->EmailAddress;
      if ($record->result) {
        $yes[] = $item;
      }
      else {
        $no[] = $item;
      }
    }

    $destination = '{{DecisionInstance(' . $this->formatGuid($instanceId) . ').Execution[' . $executionId . ']}}';
    // Now send the YESes.
    if (count($yes)) {
      $mapping['syncActions'] = [
        'destination' => $destination,
        'action' => 'setStatus',
        'status' => 'yes',
      ];
      // Sends setup/mapping array to Eloqua.
      $bulkApi->map($mapping);
      $this->tryBulkApiUpload($bulkApi, $yes, $this->logger);
      $this->logger->info(print_r($mapping, TRUE));
      $this->tryBulkApiSync($bulkApi, $this->logger);
      $status = $this->getBulkApiStatus($bulkApi);
      $msg = "Status Returned for YES: " . $status;
      $this->logger->info($msg);
    }
    if (count($no)) {
      // Repeat with the NOes.
      $mapping['syncActions'] = [
        'destination' => $destination,
        'action' => 'setStatus',
        'status' => 'no',
      ];
      // Sends setup/mapping array to Eloqua.
      $bulkApi->map($mapping);
      $this->logger->info(print_r($mapping, TRUE));
      $this->tryBulkApiUpload($bulkApi, $no, $this->logger);
      $this->tryBulkApiSync($bulkApi, $this->logger);
      $status = $this->getBulkApiStatus($bulkApi);
      $msg = "Status Returned for NO: " . $status;
      $this->logger->info($msg);
    }
  }

}
