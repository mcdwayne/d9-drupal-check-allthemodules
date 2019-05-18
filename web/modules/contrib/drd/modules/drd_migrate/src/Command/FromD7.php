<?php

namespace Drupal\drd_migrate\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Command\Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FromD7.
 *
 * @package Drupal\drd
 */
class FromD7 extends Base {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:migrate:from:d7')
      ->setDescription($this->trans('commands.drd.migrate.from.d7.description'))
      ->addArgument(
        'inventory',
        InputArgument::REQUIRED,
        $this->trans('commands.drd.migrate.from.d7.arguments.inventory')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    \Drupal::service('drd_migrate.import')->execute($input->getArgument('inventory'), $io);
  }

}
