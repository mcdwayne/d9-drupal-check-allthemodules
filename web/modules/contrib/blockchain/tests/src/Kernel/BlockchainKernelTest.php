<?php

namespace Drupal\Tests\blockchain\Kernel;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Service\BlockchainTempStoreServiceInterface;
use Drupal\blockchain_test\Service\BlockchainTestServiceInterface;
use Drupal\Core\TempStore\SharedTempStore;
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
  public static $modules = ['system', 'blockchain', 'blockchain_test'];

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
    $this->installSchema('system', ['key_value_expire']);
    $this->blockchainService = $this->container->get('blockchain.service');
    $this->assertInstanceOf(BlockchainServiceInterface::class, $this->blockchainService,
      'Blockchain service instantiated.');
    $this->blockchainTestService = $this->container->get('blockchain.test.service');
    $this->assertInstanceOf(BlockchainTestServiceInterface::class, $this->blockchainTestService,
      'Blockchain test service instantiated.');
    $this->blockchainTestService->setTestContext($this);
    $this->blockchainTestService->initConfigs();
    $this->blockchainTestService->setConfig('blockchain_block');
  }

  /**
   * Test temp store service.
   */
  public function testTempStore() {

    $tempStore = $this->blockchainService->getTempStoreService();
    $this->assertInstanceOf(BlockchainTempStoreServiceInterface::class, $tempStore, 'Tempstore service obtained.');
    $storage = $tempStore->getBlockStorage();
    $this->assertInstanceOf(SharedTempStore::class, $storage, 'Tempstore storage obtained.');
    $blocks = $tempStore->getAll();
    $this->assertEmpty($blocks, 'No blocks in tempstore yet.');
    $block = $this->blockchainService->getStorageService()->getGenericBlock();
    $tempStore->save($block);
    $blocks = $tempStore->getAll();
    $this->assertCount(1, $blocks, 'One block added to tempstore.');
    $lastBlock = $tempStore->getLastBlock();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $lastBlock, 'Last block obtained.');
    for ($i = 0; $i < 2; $i++) {
      $block = $this->blockchainService->getStorageService()->getRandomBlock($tempStore->getLastBlock()->toHash());
      $tempStore->save($block);
    }
    $blocks = $tempStore->getAll();
    $this->assertCount(3, $blocks, '3 blocks in tempstore.');
    $this->assertEquals(3, $tempStore->getBlockCount(), 'Count of blocks is 3.');
    $this->assertTrue($tempStore->anyBlock(), 'BLocks exist.');
    $firstBlock = $tempStore->getFirstBlock();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $firstBlock, 'First block ok.');
    $this->assertTrue($tempStore->checkBlocks(), 'BLocks are valid.');
    $deletedBlock = $tempStore->pop();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $deletedBlock, 'Deleted block ok.');
    $blocks = $tempStore->getAll();
    $this->assertCount(2, $blocks, '2 blocks in tempstore already.');
    $deletedBlock = $tempStore->shift();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $deletedBlock, 'Deleted block ok.');
    $blocks = $tempStore->getAll();
    $this->assertCount(1, $blocks, '1 blocks in tempstore already.');
    $tempStore->deleteAll();
    $blocks = $tempStore->getAll();
    $this->assertEmpty($blocks, 'No blocks in tempstore already.');
  }

}
