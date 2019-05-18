<?php

namespace Drupal\formassembly\Commands;

use Drupal\formassembly\FormAssemblyBatchProcessor;
use Drush\Commands\DrushCommands;

/**
 * FormAssembly Drush commandfile.
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
 */
class FormassemblyCommands extends DrushCommands {

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
   * FormassemblyCommands constructor.
   *
   * @param \Drupal\formassembly\FormAssemblyBatchProcessor $batchProcessor
   *   Our batch processing service.
   */
  public function __construct(FormAssemblyBatchProcessor $batchProcessor) {
    parent::__construct();
    $this->batchProcessor = $batchProcessor;
  }

  /**
   * Synchronize the available forms with a Formassembly endpoint..
   *
   * @usage formassembly:sync
   *   This command runs a batch sync.  It has no options.
   *
   * @command formassembly:sync
   * @aliases fas
   */
  public function sync() {
    $finished = FALSE;
    $batchConfig = [];
    try {
      $this->batchProcessor->configureBatch($batchConfig);
      $this->progressBar = $this->io()->createProgressBar();
      while (!$finished) {
        $this->batchProcessor->iterateBatch($batchConfig);
        $this->progressBar->advance();
        $finished = ($batchConfig['finished'] === 1);
      }
      $this->progressBar->finish();
      $this->io()->newLine();
      $this->io()->text(dt('Processing data received from FormAssembly'));
      $this->batchProcessor->batchPostProcess($batchConfig['sandbox']['sync_id']);
      $this->logger()->success(dt('Forms synchronized successfully'));
    }
    catch (\Exception $e) {
      $this->logger->error(dt('Sync failed due to an Exception'));
      throw $e;
    }
  }

}
