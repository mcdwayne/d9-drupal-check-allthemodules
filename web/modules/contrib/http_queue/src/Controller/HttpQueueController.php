<?php

namespace Drupal\http_queue\Controller;

use Drupal\advancedqueue\Job;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for HTTP Queue routes.
 */
class HttpQueueController extends ControllerBase {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the controller object.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, KillSwitch $kill_switch, LoggerInterface $logger) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->killSwitch = $kill_switch;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('page_cache_kill_switch'),
      $container->get('logger.factory')->get('http_queue')
    );
  }

  /**
   * Builds the response.
   */
  public function fetch(Request $request) {
    $this->accessCheck($request);
    // Get the oldest queue item with the queued status.
    $q = $this->database
      ->select('advancedqueue', 'aq')
      ->fields('aq');

    $q->condition('aq.state', Job::STATE_QUEUED);
    $q->range(0, 1);
    $q->orderBy('aq.job_id');

    $q->addTag('http_queue_fetch');

    $item = $q->execute()->fetchAssoc();
    if (!$item) {
      throw new NotFoundHttpException();
    }
    $this->moduleHandler->alter('http_queue_item_fetched', $item);
    return new JsonResponse($item);
  }

  /**
   * Claim an item.
   */
  public function claim($job_id, Request $request) {
    $this->accessCheck($request);
    $job_definition = $this->getJob($job_id);
    if (empty($job_definition)) {
      $this->logger->info('No job found with id @id', [
        '@id' => $job_id,
      ]);
      throw new NotFoundHttpException();
    }
    if ($job_definition['state'] != Job::STATE_QUEUED) {
      $this->logger->info('Tried to claim a job that is not queued');
      throw new HttpException(400, 'Can not claim a job that is not queued.');
    }
    $job_definition['id'] = $job_definition['job_id'];
    unset($job_definition['job_id']);
    $job = new Job($job_definition);
    $job->setState(Job::STATE_PROCESSING);
    $job->setProcessedTime(\Drupal::time()->getRequestTime());
    $this->saveJob($job);
    $this->moduleHandler->invokeAll('http_queue_job_claimed', [$job]);
    return new JsonResponse(['job_data' => $job]);
  }

  /**
   * Update a job.
   */
  public function complete($job_id, Request $request) {
    $this->accessCheck($request);
    $content = @json_decode($request->getContent());
    if (empty($content) || empty($content->message)) {
      $this->logger->info('No json content, or empty message of json content');
      throw new AccessDeniedHttpException();
    }
    $job_definition = $this->getJob($job_id);
    if (empty($job_definition)) {
      $this->logger->info('No job found with id @id', [
        '@id' => $job_id,
      ]);
      throw new NotFoundHttpException();
    }
    if ($job_definition['state'] == Job::STATE_QUEUED) {
      $this->logger->info('Can not update a queued job');
      throw new HttpException(400, 'Can not update a queued job. Set to processing first.');
    }
    $job_definition['id'] = $job_definition['job_id'];
    unset($job_definition['job_id']);
    $job = new Job($job_definition);
    $job->setState(Job::STATE_SUCCESS);
    $job->setMessage(json_encode($content->message));
    $this->saveJob($job);
    $this->moduleHandler->invokeAll('http_queue_job_complete', [$job]);
    return new JsonResponse(['job_data' => $job]);
  }

  /**
   * Check access based on the request.
   *
   * @throws \Exception
   */
  protected function accessCheck(Request $request) {
    $this->killSwitch->trigger();
    if (!$site_token = Settings::get('http_queue_token')) {
      // @todo: Make this a bit easier to configure.
      throw new \Exception('This module needs access configuration');
    }
    $header = $request->headers->get('x-drupal-http-queue-token');
    if (!$header) {
      $this->logger->info('No header value was found in request');
      throw new AccessDeniedHttpException();
    }
    if ($header != $site_token) {
      $this->logger->info('Header value was not equal the site token');
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Loads a job.
   */
  protected function getJob($job_id) {
    $query = $this->database->select('advancedqueue', 'a')
      ->fields('a')
      ->condition('job_id', $job_id);
    $job_definition = $query->execute()->fetchAssoc('job_id');
    $this->moduleHandler->alter('http_queue_load_job', $job_definition);
    return $job_definition;
  }

  /**
   * Saves a job.
   */
  protected function saveJob(Job $job) {
    $this->moduleHandler->alter('http_queue_save_job', $job);
    $fields = [
      'payload' => $job->getPayload(),
      'state' => $job->getState(),
      'message' => $job->getMessage(),
      'num_retries' => $job->getNumRetries(),
      'available' => $job->getAvailableTime(),
      'processed' => $job->getProcessedTime(),
      'expires' => $job->getExpiresTime(),
    ];
    $this->moduleHandler->alter('http_queue_save_job_fields', $fields);
    $this->database->update('advancedqueue')
      ->fields($fields)
      ->condition('job_id', $job->getId())
      ->execute();
  }

}
