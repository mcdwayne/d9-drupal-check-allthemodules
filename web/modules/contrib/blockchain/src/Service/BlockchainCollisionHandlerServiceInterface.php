<?php

namespace Drupal\blockchain\Service;

use Drupal\blockchain\Utils\BlockchainResponseInterface;

/**
 * Interface BlockchainCollisionHandlerServiceInterface.
 *
 * @package Drupal\blockchain\Service
 */
interface BlockchainCollisionHandlerServiceInterface {

  /**
   * Validator function.
   *
   * Grants pull based on response.
   *
   * @param \Drupal\blockchain\Utils\BlockchainResponseInterface $response
   *   Blockchain response.
   *
   * @return bool
   *   Test result.
   */
  public function isPullGranted(BlockchainResponseInterface $response);

  /**
   * Attempts to manage PULL by given endpoint address.
   *
   * @param \Drupal\blockchain\Utils\BlockchainResponseInterface $fetchResponse
   *   Fetch response.
   * @param string $endPoint
   *   Given endpoint.
   *
   * @return int|string
   *   Count of items added.
   *
   * @throws \Exception
   */
  public function processNoConflict(BlockchainResponseInterface $fetchResponse, $endPoint);

  /**
   * Attempts to manage PULL by given endpoint address.
   *
   * @param \Drupal\blockchain\Utils\BlockchainResponseInterface $fetchResponse
   *   Fetch response.
   * @param string $endPoint
   *   Given endpoint.
   *
   * @return int|string
   *   Count of items added.
   *
   * @throws \Exception
   */
  public function processConflict(BlockchainResponseInterface $fetchResponse, $endPoint);

}
