<?php

namespace Drupal\update_runner\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

/**
 * Class UpdateRunnerProcessorCommand.
 *
 * @DrupalCommand (
 *     extension="update_runner",
 *     extensionType="module"
 * )
 */
class UpdateRunnerProcessorCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('update_runner:process');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $updatesManager = \Drupal::service('update_runner.manager');
    $updatesManager->process();
  }

}
