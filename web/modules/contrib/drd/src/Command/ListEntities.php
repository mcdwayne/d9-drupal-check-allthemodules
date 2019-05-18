<?php

namespace Drupal\drd\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseInterface as ActionBaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListEntities.
 *
 * @package Drupal\drd
 */
abstract class ListEntities extends BaseSystem {

  use BaseEntitySelect;

  protected $tableHeader = [];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->configureSelection();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    /** @var array $rows */
    $rows = parent::execute($input, new NullOutput());
    $io->table($this->tableHeader, $rows, 'compact');
  }

  /**
   * {@inheritdoc}
   */
  protected function setActionArguments(ActionBaseInterface $action, InputInterface $input) {
    parent::setActionArguments($action, $input);
    $this->addSelectionAsArguments($action, $input);
  }

}
