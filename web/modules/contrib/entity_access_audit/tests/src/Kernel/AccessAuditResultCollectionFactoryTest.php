<?php

namespace Drupal\Tests\entity_access_audit\Kernel;

use Drupal\entity_access_audit\AccessAuditResultCollectionFactory;
use Drupal\entity_access_audit\Dimensions\BundleDimension;
use Drupal\entity_access_audit\Dimensions\EntityOwnerDimension;
use Drupal\entity_access_audit\Dimensions\EntityTypeDimension;
use Drupal\entity_access_audit\Dimensions\OperationDimension;
use Drupal\entity_access_audit\Dimensions\RoleDimension;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * @coversDefaultClass \Drupal\entity_access_audit\AccessAuditResultCollectionFactory
 * @group entity_access_audit
 */
class AccessAuditResultCollectionFactoryTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'user',
    'system',
    'entity_access_audit',
  ];

  /**
   * The tested factory.
   *
   * @var \Drupal\entity_access_audit\AccessAuditResultCollectionFactory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create([
      'type' => 'foo',
      'label' => 'bar',
    ])->save();
    Role::create([
      'id' => 'foo',
      'label' => 'Foo',
    ])->save();
    Role::create([
      'id' => 'bar',
      'label' => 'Bar',
    ])->save();

    $this->factory = $this->container->get('class_resolver')->getInstanceFromDefinition(AccessAuditResultCollectionFactory::class);
  }

  /**
   * @covers ::createCollectionAllDimensions
   */
  public function testCreateCollectionAllDimensions() {
    $collection = $this->factory->createCollectionAllDimensions($this->container->get('entity_type.manager')->getDefinition('node'));

    // 1 x Entity type.
    // 2 x Entity owner.
    // 2 x Role.
    // 4 x Operations.
    // 1 x Bundle.
    $this->assertEquals(16, $collection->count());

    $this->assertEquals([
      EntityTypeDimension::class,
      RoleDimension::class,
      BundleDimension::class,
      OperationDimension::class,
      EntityOwnerDimension::class,
    ], $collection->getDimensionClasses());
  }

  /**
   * @covers ::createCollectionAnonymousUserCrud
   */
  public function testCreateCollectionAnonymousUserCrud() {
    Role::create([
      'id' => 'anonymous',
      'label' => 'Anonymous user',
    ])->save();

    $collection = $this->factory->createCollectionAnonymousUserCrud($this->container->get('entity_type.manager')->getDefinition('node'));

    // 1 x Entity type.
    // 1 x Role.
    // 4 x Operations.
    $this->assertEquals(4, $collection->count());

    $this->assertEquals([
      EntityTypeDimension::class,
      RoleDimension::class,
      OperationDimension::class,
    ], $collection->getDimensionClasses());
  }

}
