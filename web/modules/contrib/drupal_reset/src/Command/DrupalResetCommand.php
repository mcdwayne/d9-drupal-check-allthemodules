<?php

namespace Drupal\drupal_reset\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\drupal_reset\DropDatabase;
use Drupal\drupal_reset\DeleteFiles;

/**
 * Class DrupalResetCommand.
 *
 * @package Drupal\drupal_reset
 */
class DrupalResetCommand extends Command {

  use CommandTrait;

  /**
   * Drupal\drupal_reset\DropDatabase definition.
   *
   * @var \Drupal\drupal_reset\DropDatabase
   */
  protected $drupalResetDropDatabase;
  /**
   * Drupal\drupal_reset\DeleteFiles definition.
   *
   * @var \Drupal\drupal_reset\DeleteFiles
   */
  protected $drupalResetDeleteFiles;

  /**
   * {@inheritdoc}
   */
  public function __construct(DropDatabase $drupal_reset_drop_database, DeleteFiles $drupal_reset_delete_files) {
    $this->drupalResetDropDatabase = $drupal_reset_drop_database;
    $this->drupalResetDeleteFiles = $drupal_reset_delete_files;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('drupal_reset')
      ->setDescription($this->trans('commands.drupal_reset.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $this->drupalResetDropDatabase->dropdatabase();
    $this->drupalResetDeleteFiles->deletefiles();

    $io->info($this->trans('commands.drupal_reset.messages.success'));
  }
}
