<?php

namespace Drupal\Tests\blockchain_test\Functional;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainRequestInterface;
use Drupal\blockchain_test\Service\BlockchainTestServiceInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests blockchain.
 *
 * @group blockchain
 * @group legacy
 */
class BlockchainFunctionalTest extends BrowserTestBase {

  const BLOCKS_COUNT = 5;

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Blockchain test service.
   *
   * @var \Drupal\blockchain_test\Service\BlockchainTestServiceInterface
   */
  protected $blockchainTestService;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['blockchain', 'blockchain_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();
    $this->assertNotEmpty($this->baseUrl, 'Base url is set.');
    $this->blockchainService = $this->container->get('blockchain.service');
    $this->assertInstanceOf(BlockchainServiceInterface::class, $this->blockchainService,
      'Blockchain service instantiated.');
    // Set test service, generate configs, set block count to 5.
    $this->blockchainTestService = $this->container->get('blockchain.test.service');
    $this->assertInstanceOf(BlockchainTestServiceInterface::class, $this->blockchainTestService,
      'Blockchain test service instantiated.');
    $this->blockchainTestService->setTestContext($this, $this->baseUrl);
    $this->blockchainTestService->initConfigs();
    $this->blockchainTestService->setConfig('blockchain_test_block');
    $this->blockchainTestService->setBlockCount(static::BLOCKS_COUNT);
    // Enable API.
    $this->blockchainTestService->setBlockchainType(BlockchainConfigInterface::TYPE_MULTIPLE);
    // Attach self to test node list.
    $this->blockchainTestService->createNode();
  }

  /**
   * Tests emulation storage API COUNT.
   */
  public function testEmulationStorageApiCount() {

    $result = $this->blockchainTestService->executeCount([], TRUE);
    $this->assertEquals(200, $result->getStatusCode(), 'Response ok');
    $this->assertEquals(static::BLOCKS_COUNT, $result->getCountParam(), 'Returned expected count of blocks');
  }

  /**
   * Tests emulation storage API FETCH.
   */
  public function testEmulationStorageApiFetch() {

    $firstBlock = $this->blockchainService->getStorageService()->getFirstBlock();
    // Switch to legacy storage.
    $this->blockchainTestService->setConfig('blockchain_block');
    // Fetch by params.
    $params = [
      BlockchainRequestInterface::PARAM_PREVIOUS_HASH => $firstBlock->getPreviousHash(),
      BlockchainRequestInterface::PARAM_TIMESTAMP => $firstBlock->getTimestamp(),
    ];
    $this->blockchainService->getApiService()->addRequiredParams($params);
    $params[BlockchainRequestInterface::PARAM_TYPE] = 'blockchain_test_block';
    $result = $this->blockchainTestService->executeFetch($params);
    $code = $result->getStatusCode();
    $this->assertEquals(200, $code, 'Response ok');
    $this->assertEquals('Success', $result->getMessageParam(), 'Response message ok');
    $this->assertTrue($result->getExistsParam(), 'Block exists');
    $this->assertEquals('Block exists', $result->getDetailsParam(), 'Response details ok');
    $this->assertEquals(4, $result->getCountParam(), 'Returned count');
    // Execute FETCH to emulation blockchain that downgrades to COUNT.
    $params = [];
    $this->blockchainService->getApiService()->addRequiredParams($params);
    $params[BlockchainRequestInterface::PARAM_TYPE] = 'blockchain_test_block';
    $result = $this->blockchainTestService->executeFetch($params);
    $code = $result->getStatusCode();
    $this->assertEquals(200, $code, 'Response ok');
    $exists = $result->getExistsParam();
    $this->assertFalse($exists, 'Block not exists');
    $count = $result->getCountParam();
    $this->assertEquals(static::BLOCKS_COUNT, $count, 'Returned total count');
  }

  /**
   * Tests emulation storage API PULL.
   */
  public function testEmulationStorageApiPull() {

    $firstBlock = $this->blockchainService->getStorageService()->getFirstBlock();
    // Switch to legacy storage.
    $this->blockchainTestService->setConfig('blockchain_block');
    // PULL by params.
    $params = [
      BlockchainRequestInterface::PARAM_PREVIOUS_HASH => $firstBlock->getPreviousHash(),
      BlockchainRequestInterface::PARAM_TIMESTAMP => $firstBlock->getTimestamp(),
      BlockchainRequestInterface::PARAM_COUNT => 4,
    ];
    $this->blockchainService->getApiService()->addRequiredParams($params);
    $params[BlockchainRequestInterface::PARAM_TYPE] = 'blockchain_test_block';
    $result = $this->blockchainTestService->executePull($params);
    $code = $result->getStatusCode();
    $this->assertEquals(200, $code, 'Response ok');
    $exists = $result->getExistsParam();
    $this->assertTrue($exists, 'Block exists');
    $blocks = $result->getBlocksParam();
    $this->assertCount(static::BLOCKS_COUNT - 1, $blocks, 'Returned 4 blocks');
    $instantiatedBlocks = [$firstBlock];
    foreach ($blocks as $key => $block) {
      $instantiatedBlocks[$key + 1] = $this->blockchainService->getStorageService()->createFromArray($block);
      $this->assertInstanceOf(BlockchainBlockInterface::class, $instantiatedBlocks[$key + 1], 'Block import ok');
    }
    $this->assertCount(static::BLOCKS_COUNT, $instantiatedBlocks, 'Blocks collected');
    $valid = $this->blockchainService->getValidatorService()->validateBlocks($instantiatedBlocks);
    $this->assertTrue($valid, 'Collected blocks are valid');
    // Execute PULL from scratch with count param only.
    $params = [
      BlockchainRequestInterface::PARAM_COUNT => static::BLOCKS_COUNT,
    ];
    $this->blockchainService->getApiService()->addRequiredParams($params);
    $params[BlockchainRequestInterface::PARAM_TYPE] = 'blockchain_test_block';
    $result = $this->blockchainTestService->executePull($params);
    $code = $result->getStatusCode();
    $this->assertEquals(200, $code, 'Response ok');
    $exists = $result->getExistsParam();
    $this->assertFalse($exists, 'Block not exists');
    $blocks = $result->getBlocksParam();
    $this->assertCount(static::BLOCKS_COUNT, $blocks, 'Returned 5 blocks');
    $instantiatedBlocks = [];
    foreach ($blocks as $block) {
      $instantiatedBlocks[] = $this->blockchainService->getStorageService()->createFromArray($block);
    }
    $this->assertCount(static::BLOCKS_COUNT, $instantiatedBlocks, 'Blocks collected');
    $valid = $this->blockchainService->getValidatorService()->validateBlocks($instantiatedBlocks);
    $this->assertTrue($valid, 'Collected blocks are valid');
    // Simulate batch PULL from scratch.
    $params = [
      BlockchainRequestInterface::PARAM_COUNT => 1,
    ];
    $this->blockchainService->getApiService()->addRequiredParams($params);
    $params[BlockchainRequestInterface::PARAM_TYPE] = 'blockchain_test_block';
    $result = $this->blockchainTestService->executePull($params);
    $code = $result->getStatusCode();
    $this->assertEquals(200, $code, 'Response ok');
    $exists = $result->getExistsParam();
    $this->assertFalse($exists, 'Block not exists');
    $blocks = $result->getBlocksParam();
    $this->assertCount(1, $blocks, 'Returned 1 block');
    $currentBlock = $this->blockchainService->getStorageService()->createFromArray(current($blocks));
    $syncBocks[] = $currentBlock;
    for ($i = 0; $i < 4; $i++) {
      $params = [
        BlockchainRequestInterface::PARAM_COUNT => 1,
        BlockchainRequestInterface::PARAM_PREVIOUS_HASH => $currentBlock->getPreviousHash(),
        BlockchainRequestInterface::PARAM_TIMESTAMP => $currentBlock->getTimestamp(),
      ];
      $this->blockchainService->getApiService()->addRequiredParams($params);
      $params[BlockchainRequestInterface::PARAM_TYPE] = 'blockchain_test_block';
      $result = $this->blockchainTestService->executePull($params);
      $code = $result->getStatusCode();
      $this->assertEquals(200, $code, 'Response ok');
      $exists = $result->getExistsParam();
      $this->assertTrue($exists, 'Block exists');
      $blocks = $result->getBlocksParam();
      $this->assertCount(1, $blocks, 'Returned 1 block');
      $currentBlock = $this->blockchainService->getStorageService()->createFromArray(current($blocks));
      $syncBocks[] = $currentBlock;
    }
    $this->assertCount(static::BLOCKS_COUNT, $instantiatedBlocks, 'Blocks collected');
    $valid = $this->blockchainService->getValidatorService()->validateBlocks($instantiatedBlocks);
    $this->assertTrue($valid, 'Collected blocks are valid');
  }

}
