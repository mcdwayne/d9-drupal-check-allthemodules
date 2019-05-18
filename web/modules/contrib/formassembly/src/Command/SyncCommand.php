<?php

namespace Drupal\formassembly\Command;

// This Annotation class is used. Drupal phpcs false negative.
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\formassembly\FormAssemblyBatchProcessor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

/**
 * FormAssembly Drupal Console Sync Command.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 *
 * @DrupalCommand (
 *     extension="formassembly",
 *     extensionType="module"
 * )
 */
class SyncCommand extends ContainerAwareCommand {

  /**
   * Injected Sync service.
   *
   * @var \Drupal\formassembly\FormAssemblyBatchProcessor
   */
  protected $batchProcessor;

  /**
   * Symfony progress bar component.
   *
   * @var \Symfony\Component\Console\Helper\ProgressBar
   */
  protected $progressBar;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormAssemblyBatchProcessor $batchProcessor) {
    $this->batchProcessor = $batchProcessor;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('formassembly:sync')
      ->setDescription($this->trans('commands.formassembly.sync.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $finished = FALSE;
    $batchConfig = [];
    $this->batchProcessor->configureBatch($batchConfig);
    $this->progressBar = $this->getIo()->createProgressBar();
    while (!$finished) {
      $this->batchProcessor->iterateBatch($batchConfig);
      $this->progressBar->advance();
      $finished = ($batchConfig['finished'] === 1);
    }
    $this->progressBar->finish();
    $this->getIo()->text($this->trans('commands.formassembly.sync.messages.processing'));
    $this->batchProcessor->batchPostProcess($batchConfig['sandbox']['sync_id']);
    $this->getIo()->success($this->trans('commands.formassembly.sync.messages.success'));
  }

}
