<?php

namespace Drupal\daemons\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\daemons\PluginDaemonManager;
use Drupal\daemons\DaemonManager;

/**
 * Class ListCommand.
 *
 * @DrupalCommand (
 *     extension="daemons",
 *     extensionType="module"
 * )
 */
class ListCommand extends Command {
  protected $dateFormatter;
  protected $pluginDaemonManager;
  protected $daemonManager;

  /**
   * ListCommand constructor.
   *
   * @param \Drupal\daemons\PluginDaemonManager $pluginDaemonManager
   *   PluginDaemonManager object.
   * @param \Drupal\daemons\DaemonManager $daemonManager
   *   DaemonManager object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   DateFormatter object.
   */
  public function __construct(PluginDaemonManager $pluginDaemonManager, DaemonManager $daemonManager, DateFormatterInterface $dateFormatter) {
    $this->pluginDaemonManager = $pluginDaemonManager;
    $this->daemonManager = $daemonManager;
    $this->dateFormatter = $dateFormatter;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('daemons:list')
      ->setDescription($this->trans('commands.daemons.list.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $plugin_service = $this->pluginDaemonManager;
    foreach ($plugin_service->getDefinitions() as $plugin_id => $plugin) {
      $instance = $plugin_service->createInstance($plugin_id);

      // Get stored daemons data.
      $data = $this
        ->daemonManager
        ->getDaemonData($plugin_id);

      // Convert last run date with short format.
      $date = '-';
      if (!empty($data['lastRunTime'])) {
        $date = $this
          ->dateFormatter
          ->format($data['lastRunTime'], 'short');
      }

      // Row data.
      // Daemon id.
      $row['id'] = $plugin_id;
      // Daemon plugin name.
      $row['title'] = $instance->getLabel();
      // Status running of daemon.
      $row['status'] = $instance->getStatus();
      // Daemon process id.
      $row['pid'] = $data['processId'];
      // Date of last running.
      $row['last_run'] = $date;

      $rows[] = $row;
    }

    $table = new Table($output);
    $table
      ->setHeaders(['Id', 'Name', 'Status', 'PID', 'Last run'])
      ->setRows($rows);
    $table->render();
  }

}
