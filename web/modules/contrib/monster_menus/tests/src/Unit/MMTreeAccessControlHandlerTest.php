<?php

namespace Drupal\Tests\monster_menus\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\monster_menus\MMTreeAccessControlHandler;

/**
 * @coversDefaultClass \Drupal\monster_menus\MMTreeAccessControlHandler
 * @group monster_menus
 */
class MMTreeAccessControlHandlerTest extends UnitTestCase {

  /**
   * The mocked class instance.
   *
   * @var \Drupal\monster_menus\MMTreeAccessControlHandler|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $MMTreeAccessControlHandler;

  /**
   * The mocked entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityType = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');

    $this->MMTreeAccessControlHandler = new TestMMTreeAccessControlHandler($this->entityType);

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($this->container);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Test for 'checkCreateAccess'.
   *
   * @covers ::checkCreateAccess
   *
   * @dataProvider providerTestCheckCreateAccess
   */
  public function testCheckCreateAccess($entity, AccountInterface $account, $contexts, AccessResult $expected_result) {
    $class = new \ReflectionClass('Drupal\monster_menus\MMTreeAccessControlHandler');
    $method = $class->getMethod('checkCreateAccess');
    $method->setAccessible(true);

    $this->assertEquals($expected_result->addCacheContexts($contexts), $method->invokeArgs($this->MMTreeAccessControlHandler, array($account, array())));
  }

  /**
   * Data provider for ::testCheckCreateAccess.
   */
  public function providerTestCheckCreateAccess() {

    $cases = [];

    $account = $this->prophesize(AccountInterface::class);
    $entity = $this->prophesize(EntityTypeInterface::class)->reveal();
    $account->hasPermission('add mm page entities')->willReturn(TRUE);
    $cases[] = [$entity, $account->reveal(), ['user.permissions'],AccessResult::allowed()];

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('add mm page entities')->willReturn(FALSE);
    $cases[] = [$entity, $account->reveal(), ['user.permissions'], AccessResult::neutral()];

    return $cases;
  }

}

/**
 * Tests MMTreeAccessControlHandler.
 */
class TestMMTreeAccessControlHandler extends MMTreeAccessControlHandler {

  /**
   * Allows access to the protected checkCreateAccess method.
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }
}