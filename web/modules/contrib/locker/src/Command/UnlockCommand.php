<?php

namespace Drupal\locker\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class UnlockCommand.
 *
 * @DrupalCommand (
 *     extension="locker",
 *     extensionType="module"
 * )
 */
class UnlockCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
        ->setName('locker:unlock')
        ->setDescription($this->trans('commands.locker.unlock.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

      $config = \Drupal::service('config.factory')->getEditable('locker.settings');
      $config->set('locker_site_locked', '')->save();
      $config->delete();
      unset($_SESSION['locker_unlocked']);
      drupal_flush_all_caches();

      $this->getIo()->info($this->trans('commands.locker.unlock.messages.unlock_success'));
  }
}
