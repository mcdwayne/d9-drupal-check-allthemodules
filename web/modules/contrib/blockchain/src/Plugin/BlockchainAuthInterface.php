<?php

namespace Drupal\blockchain\Plugin;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Class BlockchainAuthInterface.
 *
 * @package Drupal\blockchain\Plugin
 */
interface BlockchainAuthInterface extends PluginInspectionInterface {

  const LOGGER_CHANNEL = 'blockchain_auth';

  /**
   * Auth handler.
   *
   * @param \Drupal\blockchain\Utils\BlockchainRequestInterface $blockchainRequest
   *   Given request.
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface $blockchainConfig
   *   Given config object.
   *
   * @return bool
   *   Auth result.
   */
  public function authorize(BlockchainRequestInterface $blockchainRequest, BlockchainConfigInterface $blockchainConfig);

  /**
   * Setup for auth params.
   *
   * @param array $params
   *   Given params.
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface $blockchainConfig
   *   Given config object.
   */
  public function addAuthParams(array &$params, BlockchainConfigInterface $blockchainConfig);

  /**
   * Generates token for given config.
   *
   * @param BlockchainConfigInterface $blockchainConfig
   *   Blockchain config.
   *
   * @return string
   *   Token.
   */
  public function tokenGenerate(BlockchainConfigInterface $blockchainConfig);

}
