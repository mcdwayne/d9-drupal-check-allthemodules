<?php

namespace Drupal\h5p_analytics;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\h5p_analytics\Exception\MissingConfigurationException;
use Drupal\Core\Database\Connection;

/**
 * Class LrsService.
 */
class LrsService implements LrsServiceInterface {

  /**
   * Config settings
   * @var string
   */
  const SETTINGS = 'h5p_analytics.settings';

  /**
   * Default batch size
   * @var integer
   */
  const DEFAULT_BATCH_SIZE = 100;

  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queue;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new LrsService instance with injected dependencies
   *
   * @param Symfony\Component\DependencyInjection\ContainerAwareInterface $queue
   *   Queue service
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface              $config_factory
   *   Logger factory
   * @param GuzzleHttp\ClientInterface                                    $http_client
   *   Http client
   * @param Drupal\Core\Database\Connection                               $connection
   *   Database connection
   */
  public function __construct(ContainerAwareInterface $queue, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, ClientInterface $http_client, Connection $connection) {
    $this->queue = $queue;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->connection = $connection;
  }

   /**
    * {@inheritdoc}
    */
  public function getBatchSize() {
    $config = $this->configFactory->get(static::SETTINGS);
    $size = (int)$config->get('batch_size');

    return ($size > 0) ? $size : static::DEFAULT_BATCH_SIZE;
  }

  /**
   * {@inheritdoc}
   */
  public function processStatementsCron() {
    $statements = $this->queue->get('h5p_analytics_statements');

    if ($statements->numberOfItems() > 0) {
      $batches = $this->queue->get('h5p_analytics_batches');
      $size = $this->getBatchSize();

      $totalBatches = ceil($statements->numberOfItems() / $size);

      foreach (range(1, $totalBatches) as $batch) {
        $data = [];
        while((sizeof($data) < $size) && ($item = $statements->claimItem())) {
          $data[] = $item->data;
          $statements->deleteItem($item);
        }
        $batches->createItem($data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function makeStatementsHttpRequest(string $endpoint, string $key, string $secret, array $data) {
    $options = [
      'json' => $data,
      'auth' => [$key, $secret],
      'headers' => [
        'X-Experience-API-Version' => '1.0.1',
      ],
      'timeout' => 45.0,
    ];

    return $this->httpClient->post($endpoint . '/statements', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function sendToLrs(array $data, bool $bypass_request_log = FALSE) {
    $config = $this->configFactory->get(static::SETTINGS);
    $endpoint = $config->get('xapi_endpoint');
    $authUser = $config->get('key');
    $authPassword = $config->get('secret');

    if ( !( $endpoint && $authUser && $authPassword ) ) {
      throw new MissingConfigurationException('At least one of the required LRS configuration settings is missing!');
    }

    try {
      return $this->makeStatementsHttpRequest($endpoint, $authUser, $authPassword, $data);
    } catch (RequestException $e) {
      $debug = [
        'request' => [
          'url' => $url,
          'count' => is_array($data) ? sizeof($data) : 1,
        ],
        'response' => [
          'code' => $e->getCode(),
          'status' => $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '',
          'error' => $e->getMessage(),
        ]
      ];
      $this->loggerFactory->get('h5p_analytics')->error(json_encode($debug));
      if (!$bypass_request_log) {
        // TODO This could throw an exception, needs to be handled
        $this->connection->insert('h5p_analytics_request_log')
        ->fields([
          'code' => $e->getCode(),
          'reason' => $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '',
          'error' => $e->getMessage(),
          'count' => sizeof($data),
          'data' => json_encode($data),
          'created' => REQUEST_TIME,
        ])
        ->execute();
      }
      throw $e;
    } catch (\Exception $e) {
      // This one mostly happens when cURL errors occur
      $this->loggerFactory->get('h5p_analytics')->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatementStatistics() {
    $query = $this->connection->select('h5p_analytics_statement_log', 'asl')
    ->fields('asl', ['code']);
    $query->groupBy('asl.code');
    $query->addExpression('(SELECT sq.reason FROM h5p_analytics_statement_log sq WHERE sq.code = asl.code LIMIT 1)', 'reason');
    $query->addExpression('SUM(asl.count)', 'total');

    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestStatistics() {
    $query = $this->connection->select('h5p_analytics_request_log', 'arl')
    ->fields('arl', ['code']);
    $query->groupBy('arl.code');
    $query->addExpression('(SELECT sqr.reason FROM h5p_analytics_request_log sqr WHERE sqr.code = arl.code LIMIT 1)', 'reason');
    $query->addExpression('(SELECT sqe.error FROM h5p_analytics_request_log sqe WHERE sqe.code = arl.code LIMIT 1)', 'error');
    $query->addExpression('COUNT(*)', 'total');

    return $query->execute()->fetchAll();
  }

}
