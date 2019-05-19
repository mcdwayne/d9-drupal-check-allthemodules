<?php

namespace Drupal\update_runner\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

/**
 * Class UpdateRunnerFetcherCommand.
 *
 * @DrupalCommand (
 *     extension="update_runner",
 *     extensionType="module"
 * )
 */
class UpdateRunnerFetcherCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('update_runner:fetch');
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
    $this->getIo()->info('execute');
  }

}
