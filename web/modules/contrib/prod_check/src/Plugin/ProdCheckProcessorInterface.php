<?php

namespace Drupal\prod_check\Plugin;

/**
 * Defines an interface for prod check processors
 */
interface ProdCheckProcessorInterface {

  /**
   * Processes a single prod check plugin
   *
   * @param \Drupal\prod_check\Plugin\ProdCheckInterface $plugin
   */
  public function process(ProdCheckInterface $plugin);

  /**
   * Returns the info key
   *
   * @return
   *   The info key
   */
  public function info();

  /**
   * Returns the ok key
   *
   * @return
   *   The ok key
   */
  public function ok();

  /**
   * Returns the warning key
   *
   * @return
   *   The warning key
   */
  public function warning();

  /**
   * Returns the error key
   *
   * @return
   *   The error key
   */
  public function error();

}
