<?php

namespace Drupal\Tests\blockchain_test\Kernel;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain_test\Service\BlockchainTestServiceInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests blockchain.
 *
 * @group blockchain
 * @group legacy
 */
class BlockchainKernelTest extends KernelTestBase {

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
    $this->installConfig('blockchain');
    $this->installConfig('blockchain_test');
    $this->installEntitySchema('blockchain_block');
    $this->installEntitySchema('blockchain_test_block');
    $this->installEntitySchema('blockchain_node');
    $this->installEntitySchema('blockchain_config');
    $this->blockchainService = $this->container->get('blockchain.service');
    $this->assertInstanceOf(BlockchainServiceInterface::class, $this->blockchainService,
      'Blockchain service instantiated.');
    $this->blockchainTestService = $this->container->get('blockchain.test.service');
    $this->assertInstanceOf(BlockchainTestServiceInterface::class, $this->blockchainTestService,
      'Blockchain test service instantiated.');
    $this->blockchainTestService->setTestContext($this);
    $this->blockchainTestService->initConfigs();
    $this->blockchainTestService->setConfig('blockchain_test_block');
  }

  /**
   * Test emulation storage.
   */
  public function testEmulationStorage() {

    $count = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEmpty($count, 'No blocks in storage.');
    $this->assertFalse($this->blockchainService->getStorageService()->anyBlock(), 'Any block not found.');
    $affected = $this->blockchainTestService->setBlockCount(5);
    $this->assertEquals(5, $affected, 'Affected 5 blocks.');
    $count = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEquals(5, $count, 'Set count of blocks to 5.');
    $this->assertTrue($this->blockchainService->getStorageService()->anyBlock(), 'Any block found.');
    $affected = $this->blockchainTestService->setBlockCount(3);
    $this->assertEquals(2, $affected, 'Affected 2 blocks.');
    $count = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEquals(3, $count, 'Set count of blocks to 3.');
    $affected = $this->blockchainTestService->setBlockCount(3);
    $this->assertEquals(0, $affected, 'Affected 0 blocks.');
    $count = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEquals(3, $count, 'Set count of blocks to 3.');
    $affected = $this->blockchainTestService->setBlockCount(9);
    $this->assertEquals(6, $affected, 'Affected 6 blocks.');
    $count = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEquals(9, $count, 'Set count of blocks to 9.');
    // Check getters by timestamp and hash.
    $lastBlock = $this->blockchainService->getStorageService()->getLastBlock();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $lastBlock, 'Last block obtained');
    $foundLastBlock = $this->blockchainService->getStorageService()->loadByTimestampAndHash($lastBlock->getTimestamp(), $lastBlock->getPreviousHash());
    $this->assertInstanceOf(BlockchainBlockInterface::class, $foundLastBlock, 'Block found by timestamp and hash');
    $existsByTimestampAndHash = $this->blockchainService->getStorageService()->existsByTimestampAndHash($lastBlock->getTimestamp(), $lastBlock->getPreviousHash());
    $this->assertTrue($existsByTimestampAndHash, 'Defined existence by timestamp and hash');
    $blocks = $this->blockchainService->getStorageService()->getBlocksFrom($lastBlock, 100);
    $this->assertEmpty($blocks, 'No blocks loaded');
    $firstBlock = $this->blockchainService->getStorageService()->getFirstBlock();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $firstBlock, 'First block obtained');
    $this->assertEquals(1, $firstBlock->id(), 'First block id is 1');
    $blocks = $this->blockchainService->getStorageService()->getBlocksFrom($firstBlock, 100, FALSE);
    $this->assertCount(8, $blocks, 'Loaded 8 blocks');
    $this->blockchainService->getConfigService()->setCurrentConfig('blockchain_block');
    $currentConfig = $this->blockchainService->getConfigService()->getCurrentConfig();
    $this->assertEquals('blockchain_block', $currentConfig->id(), 'Current config set to native.');
    $count = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEmpty($count, 'No blocks in default storage');
  }

}
