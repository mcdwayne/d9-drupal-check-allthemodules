<?php

namespace Drupal\wsdata\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Web Service Call entities.
 */
interface WSCallInterface extends ConfigEntityInterface {

  /**
   * Override the endpoint in the active wsserver.
   */
  public function setEndpoint($endpoint);

  /**
   * Return the endpoint from the active wsserver.
   */
  public function getEndpoint();

  /**
   * Return the configured language handling plugin.
   */
  public function getLanguagePlugin();

  /**
   * Make the web service call.
   */
  public function call($method = NULL, $replacements = [], $data = NULL, $options = [], $key = NULL, $tokens = []);

  /**
   * Get the available replacement tokens.
   */
  public function getReplacements();

  /**
   * Get the available methods.
   */
  public function getMethods();

  /**
   * Get the forms provided by the wsserver for the wscall.
   */
  public function getOptionsForm();

  /**
   * Set the selected options.
   */
  public function setOptions($options);

  /**
   * Return the active options.
   */
  public function getOptions();

}
