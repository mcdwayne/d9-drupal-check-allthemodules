<?php

namespace Drupal\blockchain_test\Service;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Service\BlockchainApiServiceInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainResponse;
use Drupal\blockchain\Utils\BlockchainResponseInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\BrowserKit\Client;

/**
 * Class BlockchainTestService.
 */
class BlockchainTestService implements BlockchainTestServiceInterface {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Test context.
   *
   * @var \PHPUnit\Framework\TestCase
   */
  protected $testContext;

  /**
   * Web test context.
   *
   * @var \Drupal\Tests\BrowserTestBase
   */
  protected $webTestContext;

  /**
   * Web client.
   *
   * @var \Symfony\Component\BrowserKit\Client
   */
  protected $webClient;

  /**
   * Sase url.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * BlockchainTestService constructor.
   *
   * @param \Drupal\blockchain\Service\BlockchainServiceInterface $blockchainService
   *   Given service.
   */
  public function __construct(BlockchainServiceInterface $blockchainService) {

    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public function setTestContext(TestCase $testContext, $baseUrl = NULL) {

    $this->testContext = $testContext;
    $this->baseUrl = $baseUrl;
    if ($testContext instanceof BrowserTestBase) {
      $this->webTestContext = $testContext;
      /* @var $driver BrowserKitDriver */
      $driver = $this->webTestContext->getSession()->getDriver();
      $this->webTestContext->assertInstanceOf(BrowserKitDriver::class, $driver, 'Driver obtained');
      $this->webClient = $driver->getClient();
      $this->webTestContext->assertInstanceOf(Client::class, $this->webClient, 'Client obtained');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initConfigs($linked = TRUE, $expected = 2) {

    $this->blockchainService->getConfigService()->discoverBlockchainConfigs();
    $configs = $this->blockchainService->getConfigService()->getAll();
    $this->testContext->assertCount($expected, $configs, $expected . ' config created');
    $blockchainNodeId = $this->blockchainService->getConfigService()->generateId();
    if ($linked) {
      foreach ($this->blockchainService->getConfigService()->getAll() as $blockchainConfig) {
        $blockchainConfig->setNodeId($blockchainNodeId)->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig($configId) {

    $isSet = $this->blockchainService->getConfigService()->setCurrentConfig($configId);
    $this->testContext->assertTrue($isSet, 'Blockchain config is set.');
    $currentConfig = $this->blockchainService->getConfigService()->getCurrentConfig();
    $this->testContext->assertInstanceOf(BlockchainConfigInterface::class, $currentConfig, 'Current config set.');
    $this->testContext->assertEquals($configId, $currentConfig->id(), 'Current config setting confirmed.');
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockCount($count) {

    $affected = 0;
    if (is_numeric($count) && $count >= 0) {
      $blockCount = $this->blockchainService
        ->getStorageService()
        ->getBlockCount();
      $add = $remove = 0;
      if ($count > $blockCount) {
        $add = $count - $blockCount;
      }
      elseif ($count < $blockCount) {
        $remove = $blockCount - $count;
      }
      while ($add > 0 || $remove > 0) {
        if ($add) {
          if ($blockCount) {
            $this->blockchainService
              ->getStorageService()
              ->getRandomBlock(
                $this->blockchainService
                  ->getStorageService()
                  ->getLastBlock()
                  ->toHash()
              )->save();
          }
          else {
            $this->blockchainService
              ->getStorageService()
              ->getGenericBlock()
              ->save();
            $blockCount++;
          }
          $add--;
        }
        if ($remove) {
          $this->blockchainService
            ->getStorageService()
            ->getLastBlock()
            ->delete();
          $remove--;
        }
        $affected++;
      }
      $blockCount = $this->blockchainService->getStorageService()->getBlockCount();
      $this->testContext->assertEquals($blockCount, $count, 'Target count equals ' . $count);
      $validationResult = $this->blockchainService->getStorageService()->checkBlocks();
      $this->testContext->assertTrue($validationResult, 'Blocks in chain are valid');
    }

    return $affected;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockchainType($type) {

    $this->blockchainService->getConfigService()->getCurrentConfig()->setType($type)->save();
    $typeSet = $this->blockchainService->getConfigService()->getCurrentConfig()->getType();
    $this->testContext->assertEquals($type, $typeSet, 'Blockchain type is set.');
  }

  /**
   * {@inheritdoc}
   */
  public function createNode($baseUrl = NULL, BlockchainConfigInterface $blockchainConfig = NULL) {

    $baseUrl = ($baseUrl) ? $baseUrl : $this->baseUrl;
    $blockchainConfig = ($blockchainConfig) ? $blockchainConfig :
      $this->blockchainService->getConfigService()->getCurrentConfig();
    $blockchainNode = $this->blockchainService->getNodeService()->create(
      $blockchainConfig->id(),
      $blockchainConfig->getNodeId(),
      BlockchainNodeInterface::ADDRESS_SOURCE_CLIENT,
      $baseUrl
    );
    $this->testContext->assertInstanceOf(BlockchainNodeInterface::class, $blockchainNode, 'Blockchain node created');

    return $blockchainNode;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {

    return $this->webClient;
  }

  /**
   * {@inheritdoc}
   */
  public function executePost($url, $content = NULL) {

    $this->getClient()->request('POST', $url, [], [], [], $content);
  }

  /**
   * {@inheritdoc}
   */
  public function executeRequest($url, array $params = []) {

    $this->executePost($url, Json::encode($params));
  }

  /**
   * {@inheritdoc}
   */
  public function executeSubscribe(array $params = [], $addRequiredParams = FALSE) {

    if ($addRequiredParams) {
      $this->blockchainService->getApiService()->addRequiredParams($params);
    }
    $this->executeRequest(BlockchainApiServiceInterface::API_SUBSCRIBE, $params);
    $response = $this->getBlockchainResponse();
    $this->webTestContext
      ->assertInstanceOf(BlockchainResponseInterface::class, $response, 'Response exists.');

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function executeCount(array $params = [], $addRequiredParams = FALSE) {

    if ($addRequiredParams) {
      $this->blockchainService->getApiService()->addRequiredParams($params);
    }
    $this->executeRequest(BlockchainApiServiceInterface::API_COUNT, $params);
    $response = $this->getBlockchainResponse();
    $this->webTestContext
      ->assertInstanceOf(BlockchainResponseInterface::class, $response, 'Response exists.');

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function executeAnnounce(array $params = [], $addRequiredParams = FALSE) {

    if ($addRequiredParams) {
      $this->blockchainService->getApiService()->addRequiredParams($params);
    }
    $this->executeRequest(BlockchainApiServiceInterface::API_ANNOUNCE, $params);
    $response = $this->getBlockchainResponse();
    $this->webTestContext
      ->assertInstanceOf(BlockchainResponseInterface::class, $response, 'Response exists.');

    return [$response];
  }

  /**
   * {@inheritdoc}
   */
  public function executeFetch(array $params = [], $addRequiredParams = FALSE) {

    if ($addRequiredParams) {
      $this->blockchainService->getApiService()->addRequiredParams($params);
    }
    $this->executeRequest(BlockchainApiServiceInterface::API_FETCH, $params);
    $response = $this->getBlockchainResponse();
    $this->webTestContext
      ->assertInstanceOf(BlockchainResponseInterface::class, $response, 'Response exists.');

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function executePull(array $params = [], $addRequiredParams = FALSE) {

    if ($addRequiredParams) {
      $this->blockchainService->getApiService()->addRequiredParams($params);
    }
    $this->executeRequest(BlockchainApiServiceInterface::API_PULL, $params);
    $response = $this->getBlockchainResponse();
    $this->webTestContext
      ->assertInstanceOf(BlockchainResponseInterface::class, $response, 'Response exists.');

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockchainResponse() {

    if ($content = $this->webTestContext->getSession()->getPage()->getContent()) {
      if ($parsed = Json::decode($content)) {
        if (is_array($parsed)) {

          return BlockchainResponse::create()
            ->setParams($parsed)
            ->setStatusCode($this->webTestContext
              ->getSession()
              ->getStatusCode());
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiOpened($state) {
    if ($state) {
      // Ensure Blockchain type is 'multiple'.
      $this->blockchainService
        ->getConfigService()
        ->getCurrentConfig()
        ->setType(BlockchainConfigInterface::TYPE_MULTIPLE)->save();
      $type = $this->blockchainService->getConfigService()->getCurrentConfig()->getType();
      $this->testContext
        ->assertEquals($type, BlockchainConfigInterface::TYPE_MULTIPLE, 'Blockchain type is multiple');
    }
    else {
      // Ensure Blockchain type is 'single'.
      $this->blockchainService
        ->getConfigService()
        ->getCurrentConfig()
        ->setType(BlockchainConfigInterface::TYPE_SINGLE)->save();
      $type = $this->blockchainService->getConfigService()->getCurrentConfig()->getType();
      $this->testContext
        ->assertEquals($type, BlockchainConfigInterface::TYPE_SINGLE, 'Blockchain type is single');
    }
  }

}
