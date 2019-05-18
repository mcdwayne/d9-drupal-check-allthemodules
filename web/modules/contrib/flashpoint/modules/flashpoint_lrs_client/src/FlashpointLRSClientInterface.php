<?php
/**
 * @file
 * Provides Drupal\flashpoint_lrs_client\FlashpointLRSClientInterface;
 */
namespace Drupal\flashpoint_lrs_client;
/**
 * An interface for all FlashpointLRSClient type plugins.
 */
interface FlashpointLRSClientInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();
}