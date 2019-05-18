<?php

namespace Drupal\redis_statistics;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\statistics\StatisticsStorageInterface;
use Drupal\statistics\StatisticsViewsResult;
use Predis\Client;

class StatisticsRedisStorage implements StatisticsStorageInterface {

  /**
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * @var \Predis\ClientInterface
   */
  protected $client;

  /**
   * Construct the statistics storage.
   *
   * @param \Drupal\Core\Site\Settings $settings
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(Settings $settings, StateInterface $state, TimeInterface $time) {
    $this->settings = $settings;
    $this->state = $state;
    $this->time = $time;
    $redis_uri = $this->settings->get('redis');
    if (empty($redis_uri)) {
      throw new \RuntimeException('No Redis URI set.');
    }
    $this->client = new Client($redis_uri);
  }

  public function recordView($id) {
    $this->client->incr($id . 'totalcount');
    $this->client->incr($id . 'daycount');
    $this->client->set($id . 'timestamp', $this->time->getRequestTime());
  }

  public function fetchViews($ids) {
    $views = [];
    foreach ($ids as $id) {
      $views[$id] = $this->fetchView($id);
    }
    return $views;
  }

  public function fetchView($id) {
    return new StatisticsViewsResult(
      $this->client->get($id . 'totalcount'),
      $this->client->get($id . 'daycount'),
      $this->client->get($id . 'timestamp')
    );
  }

  public function fetchAll($order = 'totalcount', $limit = 5) {
    $keys = $this->client->keys('*' . $order);
    $ids = [];
    foreach ($keys as $key) {
      $ids[$this->client->get($key)] = str_replace($order, '', $key);
    }
    krsort($ids);
    return array_slice($ids, 0, $limit);
  }

  public function deleteViews($id) {
    // TODO: Implement deleteViews() method.
  }

  public function resetDayCount() {
    $statistics_timestamp = $this->state->get('statistics.day_timestamp') ?: 0;
    if (($this->time->getRequestTime() - $statistics_timestamp) >= 86400) {
      $this->state->set('statistics.day_timestamp', $this->time->getRequestTime());
      $keys = $this->client->keys('*daycount');
      foreach ($keys as $key) {
        $this->client->set($key, 0);
      }
    }
  }

  public function maxTotalCount() {
    // TODO: Implement maxTotalCount() method.
  }
}
