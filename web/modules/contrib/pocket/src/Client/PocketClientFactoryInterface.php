<?php

namespace Drupal\pocket\Client;

/**
 * Interface for the client factory.
 */
interface PocketClientFactoryInterface {

  /**
   * @param string $accessToken
   *
   * @return \Drupal\pocket\Client\PocketUserClientInterface
   */
  public function getUserClient(string $accessToken): PocketUserClientInterface;

  /**
   * @return \Drupal\pocket\Client\PocketAuthClient
   */
  public function getAuthClient(): PocketAuthClient;

  /**
   * Check if the API key exists.
   *
   * @return bool
   */
  public function hasKey(): bool;

}
