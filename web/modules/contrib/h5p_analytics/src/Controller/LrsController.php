<?php

namespace Drupal\h5p_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Connection;
use Drupal\h5p_analytics\LrsServiceInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Url;

/**
 * Class LrsController.
 */
class LrsController extends ControllerBase {

  /**
   * H5P analytics statements queue
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $statementsQueue;

  /**
   * H5P analytics logger
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * LRS Service
   *
   * @var Drupal\h5p_analytics\LrsServiceInterface
   */
  protected $lrs;

  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Date formatter
   *
   * @var Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Controller constructor
   *
   * @param QueueFactory                  $queueFactory
   *   Queue factory
   * @param LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory
   * @param LrsServiceInterface $lrs
   *   LRS service
   * @param Connection                    $connection
   *   Database connection
   * @param DateFormatter                  $date_formatter
   *   Date formatter
   */
  public function __construct(QueueFactory $queue_factory, LoggerChannelFactoryInterface $logger_factory, LrsServiceInterface $lrs, Connection $connection, DateFormatter $date_formatter) {
    $this->statementsQueue = $queue_factory->get('h5p_analytics_statements');
    $this->logger = $logger_factory->get('h5p_analytics');
    $this->lrs = $lrs;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Create self with injected dependencies
   *
   * @param  ContainerInterface $container
   *   DI container
   *
   * @return Drupal\h5p_analytics\Controller
   *   Controller instance
   */
  public static function create(ContainerInterface $container) {
    $queue_factory = $container->get('queue');
    $logger_factory = $container->get('logger.factory');
    $lrs = $container->get('h5p_analytics.lrs');
    $connection = $container->get('database');
    $date_formatter = $container->get('date.formatter');

    return new static($queue_factory, $logger_factory, $lrs, $connection, $date_formatter);
  }

  /**
   * xAPI
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Return response with statement data.
   */
  public function xapi(Request $request) {
    $statement = $request->request->get('statement');

    if (!$statement) {
      return new JsonResponse([], 400);
    }

    $data = json_decode($statement, TRUE);

    if (!$data) {
      return new JsonResponse([], 400);
    }

    // Set timestamp as browser side one is unreliable and statement storage in
    // LRS will involve a delay
    $data['timestamp'] = date(DATE_RFC3339);

    try {
      $this->statementsQueue->createItem($data);
    } catch (Exception $e) {
      $this->logger->error($e->getTraceAsString());
      return new JsonResponse([], 500);
    }

    return new JsonResponse($data, 200);
  }

  /**
   * LRS statistics page
   *
   * @return array
   *   Page structure definition
   */
  public function statistics() {
    $statement_stats = $this->lrs->getStatementStatistics();
    $request_stats = $this->lrs->getRequestStatistics();

    $response['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('H5P analytics LRS statistics'),
      '#attached' => [
        'library' => [
          'h5p_analytics/statistics'
        ],
        'drupalSettings' => [
          'h5pAnalyticsStatisticsData' => [
            'statements' => $statement_stats,
            'requests' => $request_stats,
          ],
        ],
      ],
    ];

    $response['statements'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['statement-statistics'],
      ],
    ];
    $response['statements']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('LRS xAPI statement statistics'),
    ];
    $response['statements']['graph'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['graph-container'],
      ],
    ];
    $response['statements']['table'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['statement-statistics'],
      ],
      '#header' => [$this->t('Code'), $this->t('Reason'), $this->t('Total')],
      '#rows' => array_map(function($single) {
        return [$single->code, $single->reason, $single->total];
      }, $statement_stats),
    ];

    $response['requests'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['request-statistics'],
      ],
    ];
    $response['requests']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('LRS xAPI statement HTTP request statistics'),
    ];
    $response['requests']['graph'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['graph-container'],
      ],
    ];
    $response['requests']['table'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['statement-statistics'],
      ],
      '#header' => [$this->t('Code'), $this->t('Reason'), $this->t('Error'), $this->t('Total')],
      '#rows' => array_map(function($single) {
        return [$single->code, $single->reason, $single->error, $single->total];
      }, $request_stats),
    ];

    return $response;
  }

  /**
   * LRS requests log page
   *
   * @return array
   *   Page structure definition
   */
  public function requests() {
    $query = $this->connection->select('h5p_analytics_request_log', 'arl')
    ->fields('arl', ['code', 'reason', 'error', 'count', 'data', 'created'])
    ->orderBy('created', 'DESC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $results = $pager->execute()->fetchAll();



    $response['table'] = [
      '#type' =>'table',
      '#header' => [$this->t('Code'), $this->t('Reason'), $this->t('Error'), $this->t('Statements'), $this->t('Timestamp'), ''],
      '#attributes' => [
        'class' => ['request-log'],
      ],
      '#empty' => $this->t('No requests found in the log'),
      '#attached' => [
        'library' => [
          'h5p_analytics/request-log'
        ],
      ],
    ];
    foreach($results as $index => $single) {
      $response['table'][$index]['code'] = [
        '#plain_text' => $single->code,
      ];
      $response['table'][$index]['reason'] = [
        '#plain_text' => $single->reason,
      ];
      $response['table'][$index]['error'] = [
        '#plain_text' => $single->error,
      ];
      $response['table'][$index]['count'] = [
        '#markup' => (int) $single->count,
      ];
      $response['table'][$index]['timestamp'] = [
        '#markup' => $this->dateFormatter->format($single->created, 'long'),
      ];
      if ($single->data && $single->data != '[]') {
        $response['table'][$index]['statements'] = [
          '#type' => 'link',
          '#title' => $this->t('Data'),
          '#url' => Url::fromUserInput('#'),
          '#attributes' => [
            'class' => ['button', 'statements'],
            'data-statements' => $single->data,
          ],
        ];
      } else {
        $response['table'][$index]['statements'] = [
          '#markup' => '',
        ];
      }
    }
    $response['pager'] = [
      '#type' => 'pager',
    ];

    return $response;
  }

}
