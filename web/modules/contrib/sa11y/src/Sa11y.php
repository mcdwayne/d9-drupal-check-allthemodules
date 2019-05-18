<?php

namespace Drupal\sa11y;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Driver\Exception\Exception;
use Drupal\sa11y\Event\Sa11yEvents;
use Drupal\sa11y\Event\Sa11yStartedEvent;
use Drupal\sa11y\Event\Sa11yCompletedEvent;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Fetches accessibility reports from a remote API.
 */
class Sa11y implements Sa11yInterface {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The settings data.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $sa11ySettings;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Constructs a Sa11y.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The DB connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, ClientInterface $http_client, RequestStack $requestStack, SerializerInterface $serializer) {
    $this->database = $database;
    $this->sa11ySettings = $config_factory->get('sa11y.settings');
    $this->httpClient = $http_client;
    $this->requestStack = $requestStack;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function getReport($reportId) {
    $query = $this->database->select('sa11y', 's')
      ->fields('s')
      ->condition('s.id', $reportId);

    $result = $query->execute()->fetchAll();
    if (isset($result[0])) {
      $result[0]->options = unserialize($result[0]->options);
      return $result[0];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getViolations($reportId, $url = NULL) {
    $query = $this->database->select('sa11y_data', 's')
      ->fields('s')
      ->condition('s.report_id', $reportId);

    if ($url) {
      $query->condition('s.url', $url);
    }

    $result = $query->execute()->fetchAll();
    return isset($result[0]) ? $result : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPending() {
    $query = $this->database->select('sa11y', 's')
      ->fields('s')
      ->condition('s.status', [static::CREATED, static::RUNNING], 'IN')
      ->orderBy('s.timestamp', 'desc');
    $result = $query->execute()->fetchAll();
    if (isset($result[0])) {
      $result[0]->options = unserialize($result[0]->options);
      return $result[0];
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createReport($single = FALSE, array $options = []) {
    $api_key = $this->sa11ySettings->get('api_key');

    // Don't create if no key or if jobs already created or running.
    if (empty($api_key) || $this->getPending()) {
      return FALSE;
    }

    if (isset($options['parameters']['include'])) {
      $options['parameters']['include'] = array_map('trim', explode("\n", $options['parameters']['include']));
    }

    if (isset($options['parameters']['exclude'])) {
      $options['parameters']['exclude'] = array_map('trim', explode("\n", $options['parameters']['exclude']));
    }

    // Add defaults.
    $options += [
      'parameters' => [
        'rules' => $this->sa11ySettings->get('rules'),
        'include' => array_map('trim', explode("\n", $this->sa11ySettings->get('include'))),
        'exclude' => array_map('trim', explode("\n", $this->sa11ySettings->get('exclude'))),
      ],
    ];

    $request_time = \Drupal::time()->getRequestTime();
    return $this->database
      ->insert('sa11y')
      ->fields([
        'type' => $single ? 'single' : 'sitemap',
        'source' => $single ?: \Drupal::request()->getSchemeAndHttpHost() . '/sitemap.xml',
        'timestamp' => $request_time,
        'status' => static::CREATED,
        'options' => serialize($options),
      ])
      ->execute();
  }

  /**
   * Helper function to set an existing record to any status.
   */
  public function setStatus($report_id, $status) {
    return $this->database
      ->update('sa11y')
      ->fields([
        'status' => $status,
      ])
      ->condition('id', $report_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function processPending() {
    if ($pending_job = $this->getPending()) {

      // Check if pending job has taken too long.
      if ($pending_job->status == static::RUNNING) {
        // @TODO: This should be a define or config.
        $timeout = 60 * 60 * 3;
        if ((\Drupal::time()->getRequestTime() - $pending_job->timestamp) > $timeout) {
          $this->setStatus($pending_job->id, static::TIMEOUT);
        }
      }
      // Attempt to initiate a new job with the API.
      else {
        if ($pending_job->status == static::CREATED) {
          if ($response = $this->send($pending_job)) {
            $response = $this->serializer->decode($response, 'json');

            if ($response['error']) {
              $this->setStatus($pending_job->id, static::ERROR);
              \Drupal::logger('sa11y')
                ->error('@message', ['@message' => $response['message']]);
            }
            else {
              $this->setStatus($pending_job->id, static::RUNNING);
              \Drupal::logger('sa11y')
                ->info('@message', ['@message' => $response['message']]);

              // Dispatch the event.
              $dispatcher = \Drupal::service('event_dispatcher');
              $dispatcher->dispatch(Sa11yEvents::STARTED, new Sa11yStartedEvent($pending_job->id));
            }
          }
          else {
            $this->setStatus($pending_job->id, static::ERROR);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestByUrl($url) {
    $query = $this->database->select('sa11y', 's')
      ->fields('s')
      ->condition('s.source', $url)
      ->orderBy('s.id', 'desc')
      ->range(0, 1);
    $result = $query->execute()->fetchAll();

    if (isset($result[0])) {
      $result[0]->options = unserialize($result[0]->options);
      return $result[0];
    }
    return FALSE;
  }

  /**
   * Finds the latest reports that includes a url in the results.
   *
   * @param string $url
   *   The url to check for.
   *
   * @return mixed
   *   A report object or FALSE.
   */
  protected function findReportByUrl($url) {
    $query = $this->database->select('sa11y_data', 'd');
    $query->addExpression('max(report_id)', 'id');
    $query->condition('d.url', $url);
    $result = $query->execute()->fetchAll();
    if (isset($result[0])) {
      return $this->getReport($result[0]->id);
    }
    return FALSE;
  }

  /**
   * Finds a the latest report based on a node object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check a report for.
   *
   * @return bool|mixed
   *   A report object or FALSE.
   */
  public function getLatestByNode(NodeInterface $node) {
    $url = $node->toUrl()->setAbsolute()->toString();

    // Check if the current path is part of the pending job or a sitemap.
    $pending = $this->getPending();
    if ($pending && $pending->source == $url) {
      return $pending;
    }

    // See if the path is in either a site run or a single.
    $latest = $this->getLatestByUrl($url);

    // Homepage in a main report wouldn't use the alias.
    if (\Drupal::service('path.matcher')->isFrontPage()) {
      $url = \Drupal::request()->getSchemeAndHttpHost() . '/';
    }
    $search = $this->findReportByUrl($url);

    // If both are found, we only want the latest.
    if ($latest && $search) {
      $report = ($latest->timestamp > $search->timestamp) ? $latest : $search;
    }
    elseif ($latest) {
      $report = $latest;
    }
    elseif ($search) {
      $report = $search;
    }
    else {
      $report = FALSE;
    }

    return $report;
  }

  /**
   * {@inheritdoc}
   */
  public function send($pending) {
    // Check for single reports to pass to server.
    if ($pending->type == 'single') {
      $pending->options['parameters']['single'] = TRUE;
    }

    $url = $this->buildUrl($pending->source, $pending->options['parameters']);
    $data = FALSE;
    try {
      $data = (string) $this->httpClient
        ->post($url, [
          'headers' => [
            'Authorization' => $this->sa11ySettings->get('api_key'),
            'Accept' => 'text/json',
          ],
        ])
        ->getBody();
    }
    catch (RequestException $exception) {
      watchdog_exception('sa11y', $exception);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function receive() {
    $request = $this->requestStack->getCurrentRequest();
    $response = $this->serializer->decode($request->getContent(), 'json');

    if (!$response['error'] && $response['report']) {

      // Get the pending report to insert into.
      if ($report = $this->getPending()) {

        // Grab the CSV.
        if ($csv = system_retrieve_file($this->sa11ySettings->get('api_server') . $response['report'], 'temporary://', FALSE)) {

          $stored_csv = \Drupal::service('file_system')->realpath($csv);

          // Set auto-detect line endings on.
          $previous = ini_set('auto_detect_line_endings', '1');

          if (($handle = fopen($stored_csv, "r")) !== FALSE) {

            // Skip Header.
            fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== FALSE) {
              try {
                $this->database->insert('sa11y_data')
                  ->fields([
                    'report_id' => $report->id,
                    'url' => $data[0],
                    'type' => $data[1],
                    'rule' => $data[2],
                    'impact' => $data[3],
                    'help' => $data[4],
                    'html' => $data[5],
                    'message' => $data[6],
                    'dom' => $data[7],
                  ])
                  ->execute();
              }
              catch (Exception $e) {
                $this->setStatus($report->id, static::ERROR);
                watchdog_exception('sa11y', $e);
              }
            }
            fclose($handle);

            // Back to original settings.
            ini_set('auto_detect_line_endings', $previous);
          }

          // Done, kill the file and set status.
          $this->setStatus($report->id, static::COMPLETE);
          file_unmanaged_delete($csv);

          // Finally, dispatch the event.
          $dispatcher = \Drupal::service('event_dispatcher');
          $dispatcher->dispatch(Sa11yEvents::COMPLETED, new Sa11yCompletedEvent($report->id));
        }
        else {
          $this->setStatus($report->id, static::ERROR);
          \Drupal::logger('sa11y')
            ->error("Could not download CSV from API.");
        }
      }
      else {
        \Drupal::logger('sa11y')
          ->error("No pending report found to insert from API.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl($path, $params = []) {
    $url = $this->sa11ySettings->get('api_server') . '/api/job';

    // Check for previous query on server uri.
    $url .= (strpos($url, '?') !== FALSE) ? '&' : '?';

    $query = [
      'siteMap' => $path,
    ];

    if (isset($params['single'])) {
      $query['single'] = $params['single'];
    }

    $url .= http_build_query($query);

    // Add in all the selected rules separately.
    $rules = !empty($params['rules']) ? $params['rules'] : $this->sa11ySettings->get('rules');
    foreach ($rules as $rule) {
      if ($rule) {
        $url .= '&rules[]=' . $rule;
      }
    }

    $includes = !empty($params['include']) ? $params['include'] : $this->sa11ySettings->get('include');
    foreach ($includes as $include) {
      if ($include) {
        $url .= '&include[]=' . urlencode($include);
      }
    }

    $excludes = !empty($params['exclude']) ? $params['exclude'] : $this->sa11ySettings->get('exclude');
    foreach ($excludes as $exclude) {
      if ($exclude) {
        $url .= '&exclude[]=' . urlencode($exclude);
      }
    }

    return $url;
  }

  /**
   * Helper to check our system reqs.
   *
   * @TODO: make this return the issue's error message.
   *
   * @return bool
   *   True if can run sa11y, false otherwise.
   */
  public function checkRequirements() {
    if (empty($this->sa11ySettings->get('api_key'))) {
      return FALSE;
    }
    return TRUE;
  }

}
