<?php

namespace Drupal\queue_monitor\Command;

use Drupal\queue_monitor\Queue\QueueProcess;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class QueueMonitorRunAllCommand.
 *
 * @DrupalCommand (
 *     extension="queue_monitor",
 *     extensionType="module"
 * )
 */
class QueueMonitorRunAllCommand extends Command {

  protected $queueProcess;

  /**
   * Constructs a new QueueMonitorRunAllCommand object.
   *
   * @param \Drupal\queue_monitor\Queue\QueueProcess $queueProcess
   */
  public function __construct(QueueProcess $queueProcess) {
    parent::__construct();
    $this->queueProcess = $queueProcess;
  }

  /**
   * @return \Drupal\queue_monitor\Queue\QueueProcess
   */
  public function getQueueProcess() {
    return $this->queueProcess;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('queue_monitor:runall')
      ->setDescription($this->trans('monitor queue'));
  }

 /**
  * {@inheritdoc}
  */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->getIo()->info('initialize');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info('Start monitor...');
    while (TRUE) {
      $this->getQueueProcess()->queueRunAll();
      $config = \Drupal::config('queue_monitor.settings');
      sleep($config->get('sleep'));
    }
  }
}
