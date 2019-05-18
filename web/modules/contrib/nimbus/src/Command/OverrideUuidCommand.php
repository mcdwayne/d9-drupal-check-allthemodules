<?php

namespace Drupal\nimbus\Command;

use Drupal\Console\Core\Command\Command;
use Drupal\nimbus\Controller\OverrideUuidController;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class OverrideUuidCommand.
 */
class OverrideUuidCommand extends Command {

  /**
   * @var \Drupal\nimbus\Controller\OverrideUuidController
   */
  private $controller;

  /**
   *
   */
  public function __construct(OverrideUuidController $controller) {
    parent::__construct();
    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('nimbus:fuuid')->getDefinition()->addOption(
      new InputOption('--yes', 'y', InputOption::VALUE_NONE, 'Equivalent to --no-interaction.')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->controller->uuidUpdateCommand($input, $output);
  }

}
