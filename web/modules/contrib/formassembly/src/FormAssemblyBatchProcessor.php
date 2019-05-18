<?php

namespace Drupal\formassembly;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * The "business logic" of the batch process for syncing with FormAssembly.
 *
 * Used by the core batch callbacks in formassembly.module and both the drush
 * and drupal console commands.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2019 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
class FormAssemblyBatchProcessor {

  use StringTranslationTrait;

  /**
   * Injected Sync service.
   *
   * @var \Drupal\formassembly\ApiSync
   */
  protected $apiSync;

  /**
   * Injected UUID service.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * FormAssemblyBatchProcessor constructor.
   *
   * @param \Drupal\formassembly\ApiSync $apiSync
   *   Injected service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   Injected service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   Injected service.
   */
  public function __construct(
    ApiSync $apiSync,
    UuidInterface $uuid,
    TranslationInterface $string_translation
  ) {
    $this->apiSync = $apiSync;
    $this->uuid = $uuid;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Configures the batch with unique id.
   *
   * @param array $config
   *   An array with batch configuration options.
   */
  public function configureBatch(array &$config) {
    /*
     * We need to provide a unique key to the sync service for this batch.
     * The service will use the key to cache results between calls.
     */
    $config['sandbox']['sync_id'] = $this->uuid->generate();
  }

  /**
   * Processes one iteration of the batch process.
   *
   * @param array $batchConfig
   *   An array with batch configuration options.
   *
   * @throws \Exception
   */
  public function iterateBatch(array &$batchConfig) {
    // Batch has started processing.  Get the cache id back from context.
    $sync_id = $batchConfig['sandbox']['sync_id'];
    try {
      $batchConfig['message'] = t('Requesting page %page of forms.', ['%page' => $this->apiSync->getPage()]);
      if ($this->apiSync->getForms($sync_id)) {
        // Returns true when finished.
        $batchConfig['finished'] = 1;
        $batchConfig['results']['sync_id'] = $sync_id;
      }
      else {
        $batchConfig['finished'] = 0.66;
      }
    }
    catch (\Exception $caught) {
      \Drupal::logger('Formassembly')
        ->error('Form batch request failed. Error message: @message', ['@message' => $caught->getMessage()]);
      throw $caught;
    }

  }

  /**
   * Completes the batch process business logic.
   *
   * @param string $sync_id
   *   The ID assigned to this sync operation.
   *
   * @throws \Exception
   */
  public function batchPostProcess($sync_id) {
    if (!empty($sync_id)) {
      $this->apiSync->syncForms($sync_id);
    }
  }

}
