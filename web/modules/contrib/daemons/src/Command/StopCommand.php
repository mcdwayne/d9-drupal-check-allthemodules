<?php

namespace Drupal\daemons\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\daemons\PluginDaemonManager;
use Drupal\daemons\DaemonManager;

/**
 * Class StopCommand.
 *
 * @DrupalCommand (
 *     extension="daemons",
 *     extensionType="module"
 * )
 */
class StopCommand extends Command {

  protected $pluginDaemonManager;
  protected $daemonManager;

  /**
   * StopCommand constructor.
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
      ->setName('daemons:stop')
      ->addArgument(
        'daemon-id',
        InputArgument::REQUIRED,
        $this->trans('commands.daemons.stop.arguments.daemon_id')
      )
      ->addArgument(
        'force',
        InputArgument::OPTIONAL,
        $this->trans('commands.daemons.stop.options.force')
      )
      ->setDescription($this->trans('commands.daemons.stop.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $daemonId = $input->getArgument('daemon-id');

    try {
      $instance = $this->pluginDaemonManager->createInstance($daemonId);

      if ($pid = $instance->getProcessId()) {
        // Default signal.
        $signal = SIGHUP;
        // Force option change signal.
        if ($input->getArgument('force')) {
          $signal = SIGKILL;
        }

        // Killing process.
        shell_exec("kill -$signal $pid");
        // Clear manager data.
        $this->daemonManager->daemonExecute('clear', $daemonId);

        $this->getIo()->success(
          sprintf(
            $this->trans(
              'commands.daemons.stop.messages.success'
            )
          )
        );
      }
      else {
        // Daemons doesn't have PID.
        $this->getIo()->warning(
          sprintf(
            $this->trans(
              'commands.daemons.stop.messages.empty_pid'
            )
          )
        );
      }
    }
    catch (\Exception $e) {
      // Daemons isn't exist.
      $this->getIo()->warning(
        sprintf(
          $this->trans(
            'commands.daemons.stop.messages.not_exist'
          )
        )
      );
    }
  }

}
