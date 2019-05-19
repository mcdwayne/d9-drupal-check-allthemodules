<?php

namespace Drupal\sl_stats;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Config\ConfigFactory;
use \Drupal\Component\Utility\Timer;

class SLStatsComputer {

  protected $efq;

  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct(ConfigFactory $config_factory, QueryFactory $efq) {
    $this->efq = $efq;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.query')
    );
  }

  public function getConfig() {
    return $this->modalidadesConfig;
  }

  function sl_stats_reset() {

    Timer::start('sl_stats_reset');
    $stop = 1;
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '1024M');

    // iterate over all players
    $efq = \Drupal::entityQuery('node');
    $efq->condition('type', 'sl_person');
    $efq->condition('status', 1);
    $result = $efq->execute();

    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('sl_stats_worker');

    if (!empty($result)) {
      foreach ($result as $entity) {
        $item = new \stdClass();
        $item->nid = $entity;
        $queue->createItem($item);
      }
    }

  }

  protected function debug($msg) {
    var_dump($msg);
  }

  public function compute($person_id) {
    ini_set("memory_limit", "-1");
    // timer_start('drp');
    Timer::start('sl_stats_compute');

    $plugin_manager = \Drupal::service('plugin.manager.sl_stats_computers');
    $computer_plugins = $plugin_manager->getDefinitions();
    $node_manager = \Drupal::entityTypeManager()->getStorage('node');
    $stats_manager = \Drupal::entityTypeManager()->getStorage('sl_stats');

    foreach ($computer_plugins as $name => $plugin) {
      $computers[$name] = $plugin_manager->createInstance($name);
    }

    $this->debug('Computing ' . $person_id);

    if (!empty($person_id)) {
      $node = $node_manager->load($person_id);
    }

    if (!empty($node)) {
      // deletes existing stats
      $efq = $efq = \Drupal::entityQuery('sl_stats');
      $efq->condition('field_sl_stats_person', $node->id());
      $result_stats = $efq->execute();
      $stats = $stats_manager->loadMultiple(array_values($result_stats));
      $stats_manager->delete($stats);
    }

    $teams = array();
    $total_stats['matches'] = $total_stats['goals'] = 0;
    if (!empty($node->field_sl_teams)) {
      foreach ($node->field_sl_teams as $team) {
        $teams[] = $team;

        // defines the computer
        foreach ($computer_plugins as $name => $plugin) {
          if ($computers[$name]->isApplicable($node, $team->entity)) {
            $computers[$name]->compute($node, $team->entity);
            continue 2;
          }
        }
      }
    }

    \Drupal::moduleHandler()->alter('sl_stats_finish', $node, $node->total_stats);
    $node->sl_stats_already_computed = TRUE;
    $node->save();

    return $node;
  }

}