<?php

namespace Drupal\ga_commerce;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface;

/**
 * Default delayed command registry implementation.
 */
class DelayedCommandRegistry implements DelayedCommandRegistryInterface {

  /**
   * The registered analytics commands.
   *
   * @var \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface[]
   */
  protected $commands;

  /**
   * The private temp store object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a new DelayedCommandRegistry object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store_factory
   *   The private temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $private_temp_store_factory) {
    $this->privateTempStore = $private_temp_store_factory->get('ga_commerce');
    $this->commands = [];
    // Workaround for https://www.drupal.org/project/drupal/issues/2860341.
    if (PHP_SAPI != 'cli') {
      $delayed_commands = $this->privateTempStore->get('commands');
      if ($delayed_commands && is_array($delayed_commands)) {
        foreach ($delayed_commands as $command) {
          $this->commands[] = $command;
        }
        $this->privateTempStore->delete('commands');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addCommand(DrupalSettingCommandsInterface $command) {
    $delayed_commands = $this->privateTempStore->get('commands');
    $delayed_commands = $delayed_commands ?: [];
    $delayed_commands[] = $command;
    $this->privateTempStore->set('commands', $delayed_commands);
  }

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    return $this->commands;
  }

}
