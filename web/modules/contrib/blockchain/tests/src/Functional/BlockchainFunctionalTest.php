<?php

namespace Drupal\Tests\blockchain\Functional;

use Drupal\blockchain\Entity\BlockchainBlockInterface;
use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Plugin\BlockchainAuthManager;
use Drupal\blockchain\Service\BlockchainApiServiceInterface;
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

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Test service helper.
   *
   * @var BlockchainTestServiceInterface
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
    $this->assertNotEmpty($this->baseUrl,'Base url exists.');
    $this->blockchainService = $this->container->get('blockchain.service');
    $this->assertInstanceOf(BlockchainServiceInterface::class,
      $this->blockchainService, 'Blockchain service instantiated.');
    $this->blockchainTestService = $this->container->get('blockchain.test.service');
    $this->assertInstanceOf(BlockchainTestServiceInterface::class, $this->blockchainTestService,
      'Blockchain test service instantiated.');
    $this->blockchainTestService->setTestContext($this);
    $this->blockchainTestService->initConfigs(TRUE);
    $this->blockchainTestService->setConfig('blockchain_block');
  }

  /**
   * Tests validation handler for blockchain API.
   */
  public function testBlockchainApiValidation() {

    // Cover method checking.
    $this->drupalGet(BlockchainApiServiceInterface::API_SUBSCRIBE);
    $this->assertEquals(400, $this->getSession()->getStatusCode());
    $this->assertContains('{"message":"Bad request","details":"Incorrect method."}', $this->getSession()->getPage()->getContent());
    // Try to POST. With no params.
    $response = $this->blockchainTestService->executeSubscribe([]);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('Bad request', $response->getMessageParam());
    $this->assertEquals('Missing type param.', $response->getDetailsParam());
    $allowNotSecure = $this->blockchainService->getConfigService()->getCurrentConfig()->getAllowNotSecure();
    $this->assertTrue($allowNotSecure, 'Secure protocol not required by default');
    // Try to access with invalid type.
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_TYPE => 'non_existent_type',
    ]);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('Bad request', $response->getMessageParam());
    $this->assertEquals('Invalid type param.', $response->getDetailsParam());
    // Try to access with incorrect protocol, test server should use [http://].
    $this->blockchainService->getConfigService()->getCurrentConfig()->setAllowNotSecure(FALSE)->save();
    $allowNotSecure = $this->blockchainService->getConfigService()->getCurrentConfig()->getAllowNotSecure();
    $this->assertFalse($allowNotSecure, 'Secure protocol is required now');
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_TYPE => 'blockchain_block',
    ]);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('Bad request', $response->getMessageParam());
    $this->assertEquals('Incorrect protocol.', $response->getDetailsParam());
    $this->blockchainService->getConfigService()->getCurrentConfig()->setAllowNotSecure(TRUE)->save();
    $allowNotSecure = $this->blockchainService->getConfigService()->getCurrentConfig()->getAllowNotSecure();
    $this->assertTrue($allowNotSecure, 'Secure protocol not required again');
    // Ensure Blockchain type is 'single' (closed).
    $this->blockchainTestService->setApiOpened(FALSE);
    $blockchainNodeId = $this->blockchainService->getConfigService()->getCurrentConfig()->getNodeId();
    // Ensure Blockchain 'auth' is 'none' by default.
    $auth = $this->blockchainService->getConfigService()->getCurrentConfig()->getAuth();
    $this->assertEquals(BlockchainAuthManager::DEFAULT_PLUGIN, $auth, 'Blockchain set to none');
    // Cover API is restricted for 'single' type. Request adds required params.
    $response = $this->blockchainTestService->executeSubscribe([], TRUE);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals('Forbidden', $response->getMessageParam());
    $this->assertEquals('Access to this resource is restricted.', $response->getDetailsParam());
    // Ensure Blockchain type is 'multiple' (open).
    $this->blockchainTestService->setApiOpened(TRUE);
    // Try to access with no 'self' param.
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_TYPE => 'blockchain_block',
    ]);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('Bad request', $response->getMessageParam());
    $this->assertEquals('No self param.', $response->getDetailsParam());
    // Enable auth.
    $this->blockchainService->getConfigService()->getCurrentConfig()->setAuth('shared_key')->save();
    $auth = $this->blockchainService->getConfigService()->getCurrentConfig()->getAuth();
    $this->assertEquals('shared_key', $auth, 'Blockchain auth is enabled');
    // Blockchain id is generated on first request, lets check it. (this is shared key)
    $blockchainId = $this->blockchainService->getConfigService()->getCurrentConfig()->getBlockchainId();
    $this->assertNotEmpty($blockchainId, 'Blockchain id is generated.');
    // Cover API is restricted for non 'auth' request.
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_SELF => $blockchainNodeId,
      BlockchainRequestInterface::PARAM_TYPE => 'blockchain_block',
    ]);
    $this->assertEquals(401, $response->getStatusCode());
    $this->assertEquals('Unauthorized', $response->getMessageParam());
    $this->assertEquals('Auth token invalid.', $response->getDetailsParam());
    // Cover API is restricted for invalid 'auth' request.
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_SELF => $blockchainNodeId,
      BlockchainRequestInterface::PARAM_AUTH => 'invalid_token',
      BlockchainRequestInterface::PARAM_TYPE => 'blockchain_block',
    ]);
    $this->assertEquals(401, $response->getStatusCode());
    $this->assertEquals('Unauthorized', $response->getMessageParam());
    $this->assertEquals('Auth token invalid.', $response->getDetailsParam());
    // Test not subscribed yet test case. (Use ANNOUNCE)
    // This is basically auth success test case as this is supposed to be passed.
    $response = $this->blockchainTestService->executeCount([], TRUE);
    $this->assertEquals(401, $response->getStatusCode());
    $this->assertEquals('Unauthorized', $response->getMessageParam());
    $this->assertEquals('Not subscribed yet.', $response->getDetailsParam());
    // Disable auth.
    $this->blockchainService->getConfigService()->getCurrentConfig()->setAuth(BlockchainAuthManager::DEFAULT_PLUGIN)->save();
    $auth = $this->blockchainService->getConfigService()->getCurrentConfig()->getAuth();
    $this->assertEquals(BlockchainAuthManager::DEFAULT_PLUGIN, $auth, 'Blockchain auth is disabled');
    // For ip filtering we should success in subscribe to find out ip of our client.
    $response = $this->blockchainTestService->executeSubscribe([],TRUE);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('Success', $response->getMessageParam());
    $this->assertEquals('Added to list.', $response->getDetailsParam());
    $blockchainNodeId = $this->blockchainService->getConfigService()->getCurrentConfig()->getNodeId();
    $blockchainNode = $this->blockchainService->getNodeService()->loadBySelfAndType(
      $blockchainNodeId, $this->blockchainService->getConfigService()->getCurrentConfig()->id());
    $this->assertNotEmpty($blockchainNode, 'Node exists');
    $ip = $blockchainNode->getAddress();
    $whitelist = $this->getIpWhitelist($ip);
    $blacklist = $this->getIpBlackList($ip);
    $this->assertNotEmpty($ip, 'Ip exists');
    // Ensure we have blacklist filter mode.
    $blockchainFilterType = $this->blockchainService->getConfigService()->getCurrentConfig()->getFilterType();
    $this->assertEquals($blockchainFilterType, BlockchainConfigInterface::FILTER_TYPE_BLACKLIST, 'Blockchain filter type is blacklist');
    $configList = $this->blockchainService->getConfigService()->getCurrentConfig()->getFilterList();
    $this->assertEmpty($configList, 'Blockchain blacklist is empty');
    $this->blockchainService->getConfigService()->getCurrentConfig()->setBlockchainFilterListAsArray($blacklist)->save();
    // Ensure we included our ip in black list.
    $configList = $this->blockchainService->getConfigService()->getCurrentConfig()->getBlockchainFilterListAsArray();
    $this->assertEquals($blacklist, $configList, 'Blacklist is equal to expected.');
    // Cover check for blacklist.
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_SELF => $blockchainNodeId,
      BlockchainRequestInterface::PARAM_TYPE => 'blockchain_block',
    ]);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals('Forbidden', $response->getMessageParam());
    $this->assertEquals('You are forbidden to access this resource.', $response->getDetailsParam());
    // Ensure we have whitelist filter mode.
    $this->blockchainService->getConfigService()->getCurrentConfig()->setFilterType(BlockchainConfigInterface::FILTER_TYPE_WHITELIST)->save();
    $blockchainFilterType = $this->blockchainService->getConfigService()->getCurrentConfig()->getFilterType();
    $this->assertEquals($blockchainFilterType, BlockchainConfigInterface::FILTER_TYPE_WHITELIST, 'Blockchain filter type is whitelist');
    // Ensure put ip is not in whitelist.
    $this->blockchainService->getConfigService()->getCurrentConfig()->setBlockchainFilterListAsArray($whitelist)->save();
    $configList = $this->blockchainService->getConfigService()->getCurrentConfig()->getBlockchainFilterListAsArray();
    $this->assertEquals($configList, $whitelist, 'Whitelist set.');
    // Cover check for whitelist.
    $response = $this->blockchainTestService->executeSubscribe([
      BlockchainRequestInterface::PARAM_SELF => $blockchainNodeId,
      BlockchainRequestInterface::PARAM_TYPE => 'blockchain_block',
    ]);
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals('Forbidden', $response->getMessageParam());
    $this->assertEquals('You are forbidden to access this resource.', $response->getDetailsParam());
    // Lets reset this for further testing.
    $this->blockchainService->getConfigService()->getCurrentConfig()->setBlockchainFilterListAsArray([])->save();
    $configList = $this->blockchainService->getConfigService()->getCurrentConfig()->getFilterList();
    $this->assertEmpty($configList, 'Whitelist is empty.');
    //Blockchain node was previously created.
    //Ensure list is not empty.
    $blockchainNodeExists = $this->blockchainService->getNodeService()->existsBySelfAndType(
      $blockchainNodeId, $this->blockchainService->getConfigService()->getCurrentConfig()->id());
    $this->assertTrue($blockchainNodeExists, 'Blockchain node exists in list');
    $nodeCount = $this->blockchainService->getNodeService()->getList();
    $this->assertCount(1, $nodeCount, 'Blockchain node list not empty');
    // Cover 'already exists' use case. Use required params attached.
    $response = $this->blockchainTestService->executeSubscribe([], TRUE);
    $this->assertEquals(406, $response->getStatusCode());
    $this->assertEquals('Not acceptable', $response->getMessageParam());
    $this->assertEquals('Already in list.', $response->getDetailsParam());
    // Delete node.
    $this->blockchainService->getNodeService()->delete($blockchainNode);
    $blockchainNodeExists = $this->blockchainService->getNodeService()->existsBySelfAndType(
      $blockchainNodeId, $this->blockchainService->getConfigService()->getCurrentConfig()->id());
    $this->assertFalse($blockchainNodeExists, 'Blockchain node not exists in list');
    $nodeCount = $this->blockchainService->getNodeService()->getList();
    $this->assertEmpty($nodeCount, 'Blockchain node list empty');
  }

  /**
   * Tests that default values are correctly translated to UUIDs in config.
   */
  public function testBlockchainServiceSubscribe() {

    $this->blockchainTestService->setApiOpened(TRUE);
    // Test subscribe method.
    $response = $this->blockchainTestService->executeSubscribe([],TRUE);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('Success', $response->getMessageParam());
    $this->assertEquals('Added to list.', $response->getDetailsParam());
    $blockchainNodeId = $this->blockchainService->getConfigService()->getCurrentConfig()->getNodeId();
    $blockchainNodeExists = $this->blockchainService->getNodeService()->existsBySelfAndType(
        $blockchainNodeId, $this->blockchainService->getConfigService()->getCurrentConfig()->id());
    $this->assertTrue($blockchainNodeExists, 'Blockchain node exists in list');
    $testLoad = $this->blockchainService->getNodeService()->loadBySelfAndType(
      $blockchainNodeId, $this->blockchainService->getConfigService()->getCurrentConfig()->id());
    $this->assertInstanceOf(BlockchainNodeInterface::class, $testLoad, 'Blockchain node loaded');
    $testLoad = $this->blockchainService->getNodeService()->load('NON_EXISTENT');
    $this->assertEmpty($testLoad, 'Non existent Blockchain node not loaded');
    $testLoad = $this->blockchainService->getNodeService()->exists('NON_EXISTENT');
    $this->assertFalse($testLoad, 'Non existent Blockchain node not loaded');
  }

  /**
   * Tests that default values are correctly translated to UUIDs in config.
   */
  public function testBlockchainServiceAnnounce() {

    $this->blockchainTestService->setApiOpened(TRUE);
    // Ensure none blocks in blockchain.
    $this->assertFalse($this->blockchainService->getStorageService()->anyBlock(), 'Any block returns false');
    $blockCount = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertEmpty($blockCount, 'None blocks in storage yet.');
    // Create generic block and add it to blockchain.
    $genericBlock = $this->blockchainService->getStorageService()->getGenericBlock();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $genericBlock, 'Generic block created.');
    $this->blockchainService->getStorageService()->save($genericBlock);
    $blockCount = $this->blockchainService->getStorageService()->getBlockCount();
    $this->assertTrue($this->blockchainService->getStorageService()->anyBlock(), 'Any block returns true');
    $this->assertNotEmpty($blockCount, 'Generic block added to storage.');
    $lastBlock = $this->blockchainService->getStorageService()->getLastBlock();
    $this->assertInstanceOf(BlockchainBlockInterface::class, $lastBlock, 'Last block obtained');
    $this->assertEquals(1, $lastBlock->id(), 'Last block id obtained');
    $blockByTimestampAndHash = $this->blockchainService->getStorageService()->loadByTimestampAndHash(
      $lastBlock->getTimestamp(), $lastBlock->getPreviousHash()
    );
    $this->assertInstanceOf(BlockchainBlockInterface::class, $blockByTimestampAndHash, 'Block by Timestamp and previous hash block obtained');
    // Set announce handling to CRON (no immediate) processing.
    $announceManagement = $this->blockchainService->getConfigService()->getCurrentConfig()->getAnnounceManagement();
    $this->assertEquals(BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_IMMEDIATE, $announceManagement, 'Announce management is immediate.');
    $this->blockchainService->getConfigService()->getCurrentConfig()->setAnnounceManagement(BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_CRON)->save();
    $announceManagement = $this->blockchainService->getConfigService()->getCurrentConfig()->getAnnounceManagement();
    $this->assertEquals(BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_CRON, $announceManagement, 'Announce management set to CRON handled.');
    // Attach self to node list.
    $blockchainNodeId = $this->blockchainService->getConfigService()->getCurrentConfig()->getNodeId();
    $this->blockchainTestService->createNode();
    $blockchainNodeExists = $this->blockchainService->getNodeService()->existsBySelfAndType(
      $blockchainNodeId, $this->blockchainService->getConfigService()->getCurrentConfig()->id());
    $this->assertTrue($blockchainNodeExists, 'Blockchain node exists in list');
    $nodeCount = $this->blockchainService->getNodeService()->getList();
    $this->assertCount(1, $nodeCount, 'Blockchain node list not empty');
    // Repeat announce and ensure it was passed to self as node.
    $announceCount = $this->blockchainTestService->executeAnnounce([
      BlockchainRequestInterface::PARAM_COUNT => $this->blockchainService->getStorageService()->getBlockCount(),
    ], TRUE);
    $this->assertCount(1, $announceCount, 'Announce was related to one node.');
    $this->assertEquals(406, current($announceCount)->getStatusCode(), 'Status code for announce response is 406.');
    $processedAnnounces = $this->blockchainService->getQueueService()->doAnnounceHandling();
    // Ensure no announces processed as it was 406 (Count of blocks equals).
    $this->assertEquals(0, $processedAnnounces, 'No announces were processed.');
    // Try to emulate announce queue inclusion by fake count of blocks 2.
    $announceCount = $this->blockchainTestService->executeAnnounce([
      BlockchainRequestInterface::PARAM_COUNT => 2,
    ], TRUE);
    $this->assertCount(1, $announceCount, 'Announce was related to one node.');
    $this->assertEquals(200, current($announceCount)->getStatusCode(), 'Status code for announce response is 200.');
    // Ensure 1 announce was processed as it was 200 (Due to fake count '2').
    $processedAnnounces = $this->blockchainService->getQueueService()->doAnnounceHandling();
    $this->assertEquals(1, $processedAnnounces, 'One announce was processed.');
    // In this case item was processed but taken no action as
    // FETCH should have found that count of blocks equals.
  }

  /**
   * Getter for ips, ensures given ip is included into list.
   *
   * @param string $ip
   *   Ip to be included in list.
   *
   * @return string[]
   *   Array of ips.
   */
  protected function getIpBlackList($ip) {

    $ipList = [$this->randomIp(), $this->randomIp(),  $this->randomIp()];
    if (($key = array_search($ip, $ipList) === FALSE)) {
      $ipList[] = $ip;
    }

    return $ipList;
  }

  /**
   * Getter for ips, ensures given ip is not included into list.
   *
   * @param string $ip
   *   Ip to be not included in list.
   *
   * @return string[]
   *   Array of ips.
   */
  protected function getIpWhitelist($ip) {

    $ipList = [$this->randomIp(), $this->randomIp(),  $this->randomIp()];
    if (($key = array_search($ip, $ipList) !== FALSE)) {
      unset($ipList[$key]);
    }

    return $ipList;
  }

  /**
   * Ip generator.
   *
   * @return string
   *   Array of random ips.
   */
  protected function randomIp() {

    return mt_rand(0,255).".".mt_rand(0,255).".".mt_rand(0,255).".".mt_rand(0,255);
  }

}
