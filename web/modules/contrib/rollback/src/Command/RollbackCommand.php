<?php

namespace Drupal\rollback\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Core\State\StateInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\rollback\Rollback;

/**
 * Class RollbackCommand.
 *
 * @DrupalCommand (
 *     extension="rollback",
 *     extensionType="module"
 * )
 */
class RollbackCommand extends ContainerAwareCommand {

  /**
   * Implements the state system.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Implements the chain queue.
   *
   * @var Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * Implements the database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Implements the rollback.
   *
   * @var Drupal\rollback\Rollback
   */
  protected $rollback;

  /**
   * RollbackCommand's constructor.
   *
   * @param Drupal\Core\State\StateInterface $state
   *   Defines the interface for the state system.
   * @param Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   Class ChainQueue.
   * @param Drupal\rollback\Rollback $rollback
   *   Handles performing an actual rollback.
   */
  public function __construct(
    StateInterface $state,
    ChainQueue $chainQueue,
    Rollback $rollback
  ) {
    $this->state = $state;
    $this->chainQueue = $chainQueue;
    $this->database = $database;
    $this->rollback = $rollback;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('update:rollback')
      ->setDescription($this->trans('commands.update.rollback.description'))
      ->addArgument(
        'module',
        InputArgument::REQUIRED,
        $this->trans('commands.update.rollback.arguments.module')
      )
      ->addArgument(
        'schema',
        InputArgument::REQUIRED,
        $this->trans('commands.update.rollback.arguments.schema')
      )
      ->setAliases(['rbdb']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Place the site in to maintenance mode while the update is
    // rolled back.
    $this->state->set('system.maintenance_mode', TRUE);
    $this->getIo()->info($this->trans('commands.site.maintenance.messages.maintenance-on'));

    // Retrieve argument values.
    $module = $input->getArgument('module');
    $schema = $input->getArgument('schema');

    $rollbacks = $this->rollback->run($module, $schema);

    if (is_array($rollbacks)) {
      foreach ($rollbacks as $update) {
        $this->getIo()->success('Rolled back ' . unserialize($update->target) . ' @ schema ' . $update->schema_version);
      }
    }
    elseif (!$rollbacks) {
      $this->getIo()->error($this->trans('commands.update.rollback.messages.no-rollbacks'));
    }

    // Take the site out of maintenance mode.
    $this->state->set('system.maintenance_mode', FALSE);
    $this->getIo()->info($this->trans('commands.site.maintenance.messages.maintenance-off'));

    // Clear the cache.
    if (is_array($rollbacks)) {
      $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
  }

}
