<?php

namespace Drupal\blockchain_test\Service;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class BlockchainTestService.
 */
interface BlockchainTestServiceInterface {

  /**
   * Setter for test context.
   *
   * @param \PHPUnit\Framework\TestCase $testContext
   *   Test context.
   * @param null|string $baseUrl
   *   Base url.
   */
  public function setTestContext(TestCase $testContext, $baseUrl = NULL);

  /**
   * Initializes configs.
   *
   * @param bool $linked
   *   Defines if all configs will have same block node id.
   */
  public function initConfigs($linked = TRUE);

  /**
   * Setter for current config.
   *
   * Can be set: 'blockchain_test_block' or 'blockchain_block'.
   *
   * @param string $configId
   *   Id of config.
   */
  public function setConfig($configId);

  /**
   * Sets specific count of blocks for storage.
   *
   * @param int $count
   *   Count of blocks to be set.
   *
   * @return int
   *   Count of affected blocks.
   */
  public function setBlockCount($count);

  /**
   * Setter for blockchain type.
   *
   * This can be: BlockchainConfigInterface::TYPE_MULTIPLE|TYPE_SINGLE.
   *
   * @param string $type
   *   Type of blockchain.
   */
  public function setBlockchainType($type);

  /**
   * Creates simple blockchain node by given params.
   *
   * @param null|string $baseUrl
   *   Base url.
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface|null $blockchainConfig
   *   Blockchain config if any.
   *
   * @return \Drupal\blockchain\Entity\BlockchainNodeInterface
   */
  public function createNode($baseUrl = NULL, BlockchainConfigInterface $blockchainConfig = NULL);

  /**
   * Getter for web client.
   *
   * @return null|\Symfony\Component\BrowserKit\Client
   *   Getter for web client.
   */
  public function getClient();

  /**
   * Executes POST request.
   *
   * @param string $url
   *   Given url(can be relative).
   * @param null|string $content
   *   Given content string.
   */
  public function executePost($url, $content = NULL);

  /**
   * Executes POST request, converts params to JSON.
   *
   * @param $url
   *   Given api Url.
   * @param array $params
   *   Given params.
   */
  public function executeRequest($url, array $params = []);

  /**
   * Executes SUBSCRIBE.
   *
   * @param array $params
   *   Given params.
   * @param bool $addRequiredParams
   *   Defines if required params should be added by API service.
   *
   * @return \Drupal\blockchain\Utils\BlockchainResponseInterface|null
   *   Current blockchain response if any.
   */
  public function executeSubscribe(array $params = [], $addRequiredParams = FALSE);

  /**
   * Executes COUNT.
   *
   * @param array $params
   *   Given params.
   * @param bool $addRequiredParams
   *   Defines if required params should be added by API service.
   *
   * @return \Drupal\blockchain\Utils\BlockchainResponseInterface|null
   *   Current blockchain response if any.
   */
  public function executeCount(array $params = [], $addRequiredParams = FALSE);

  /**
   * Executes ANNOUNCE.
   *
   * @param array $params
   *   Given params.
   * @param bool $addRequiredParams
   *   Defines if required params should be added by API service.
   *
   * @return \Drupal\blockchain\Utils\BlockchainResponseInterface|null
   *   Current blockchain response if any.
   */
  public function executeAnnounce(array $params = [], $addRequiredParams = FALSE);

  /**
   * Executes FETCH.
   *
   * @param array $params
   *   Given params.
   * @param bool $addRequiredParams
   *   Defines if required params should be added by API service.
   *
   * @return \Drupal\blockchain\Utils\BlockchainResponseInterface|null
   *   Current blockchain response if any.
   */
  public function executeFetch(array $params = [], $addRequiredParams = FALSE);

  /**
   * Executes PULL.
   *
   * @param array $params
   *   Given params.
   * @param bool $addRequiredParams
   *   Defines if required params should be added by API service.
   *
   * @return \Drupal\blockchain\Utils\BlockchainResponseInterface|null
   *   Current blockchain response if any.
   */
  public function executePull(array $params = [], $addRequiredParams = FALSE);

  /**
   * Getter for current blockchain response if any.
   *
   * @return \Drupal\blockchain\Utils\BlockchainResponseInterface|null
   *   Current blockchain response if any.
   */
  public function getBlockchainResponse();

  /**
   * Opens/closes API for external access.
   *
   * @param bool $state
   *   Boolean value expected.
   */
  public function setApiOpened($state);
}
