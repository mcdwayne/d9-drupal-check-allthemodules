<?php

namespace Drupal\daemons\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\daemons\PluginDaemonManager;
use Drupal\daemons\DaemonManager;
use React\EventLoop\Factory;

/**
 * Class RunCommand.
 *
 * @DrupalCommand (
 *     extension="daemons",
 *     extensionType="module"
 * )
 */
class RunCommand extends Command {
  protected $pluginDaemonManager;
  protected $daemonManager;

  /**
   * RunCommand constructor.
   *
   * @param \Drupal\daemons\PluginDaemonManager $pluginDaemonManager
   *   PluginDaemonManager object.
   * @param \Drupal\daemons\DaemonManager $daemonManager
   *   DaemonManager object.
   */
  public function __construct(PluginDaemonManager $pluginDaemonManager, DaemonManager $daemonManager) {
    $this->pluginDaemonManager = $pluginDaemonManager;
    $this->daemonManager = $daemonManager;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('daemons:run')
      ->addArgument(
        'daemon-id',
        InputArgument::REQUIRED
      )
      ->setDescription($this->trans('commands.daemons.run.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $daemonId = $input->getArgument('daemon-id');
    $instance = $this->pluginDaemonManager->createInstance($daemonId);
    $daemonManager = $this->daemonManager;

    $loop = Factory::create();

    // Soft stop of daemon by catching SIGHUP SIGNAL.
    $loop->addSignal(SIGHUP, function (int $signal) use ($loop, $daemonManager, $daemonId) {
      $loop->futureTick(function () use ($loop, $daemonManager, $daemonId) {
        $loop->stop();
        $daemonManager->daemonExecute('clear', $daemonId);
      });
    });

    if ($timer = $instance->getPeriodicTimer()) {
      // Set php process id.
      $instance->storeDaemonData(getmypid());
      $loop->addPeriodicTimer($timer, function () use ($instance, $loop) {
        // Check daemon process.
        // If real daemon id is not equal to stored id we kills process.
        $instance->checkDaemonProcessId();

        // Update last run time for daemon if it is running.
        $instance->updateLastRunTime();

        // Execute daemon.
        $instance->execute($loop);
      });
    }
    else {
      $instance->execute($loop);
    }
    $loop->run();
  }

}
