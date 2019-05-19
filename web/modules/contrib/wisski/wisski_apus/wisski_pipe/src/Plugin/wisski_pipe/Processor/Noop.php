<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\Noop.
 */

namespace Drupal\wisski_pipe\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;


/**
 * @Processor(
 *   id = "noop",
 *   label = @Translation("No Operation"),
 *   description = @Translation("Just passes the data on without doing anything."),
 *   tags = { "noop", "filler" }
 * )
 */
class Noop extends ProcessorBase {
  
  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  
  /**
   * {@inheritdoc}
   */
  public function doRun() {
  }

}
