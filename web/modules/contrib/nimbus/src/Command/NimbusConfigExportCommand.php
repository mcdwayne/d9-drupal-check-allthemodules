<?php

namespace Drupal\nimbus\Command;

use Drupal\Console\Core\Command\Command;
use Drupal\nimbus\Controller\NimbusExportController;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class NimbusConfigExportCommand.
 */
class NimbusConfigExportCommand extends Command {

  /**
   * @var \Drupal\nimbus\Controller\NimbusExportController
   */
  private $controller;

  /**
   *
   */
  public function __construct(NimbusExportController $controller) {
    parent::__construct();
    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('nimbus:export')->getDefinition()->addOption(
      new InputOption('--yes', 'y', InputOption::VALUE_NONE, 'Equivalent to --no-interaction.')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->checkBasicFlags($input, $output);
    $this->controller->configurationExport($input, $output);
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function checkBasicFlags(InputInterface $input, OutputInterface $output) {
    if ($input->getParameterOption([
      '--yes',
      '-y',
      '--no',
      '-n',
    ], FALSE, TRUE) !== FALSE) {
      $input->setInteractive(FALSE);
    }

    if ($input->getParameterOption([
      '--verbose',
      '-v',
    ], FALSE, TRUE) !== FALSE) {
      $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    }
  }

}
