<?php
namespace Drupal\sfs\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\sfs\SfsRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SfsQueueWorker
 * 
 * @QueueWorker (
 *  id = "sfs_queue_worker",
 *  title = @Translation("Cron Stop Forum Spam Client"),
 *  cron = {"time" = 30},
 * )
 */
class SfsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\sfs\SfsRequest
   */
  protected $sfsRequest;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SfsRequest $sfs_request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sfsRequest = $sfs_request;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $sfsRequest = $container->get('sfs.detect.spam');
    
    return new static($configuration, $plugin_id, $plugin_definition, $sfsRequest);
  }
  
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->sfsRequest->checkUsers();
  }
}
