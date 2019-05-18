<?php

namespace Drupal\Tests\migrate_override\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\migrate_override\OverrideManagerService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the migration override service.
 *
 * @group migrate_override
 *
 * @coversDefaultClass \Drupal\migrate_override\OverrideManagerService
 */
class OverrideServiceTest extends UnitTestCase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The Entity Display Repository prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityDisplayRepository;

  /**
   * The config prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * A page entity prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $pageEntity;

  /**
   * An article entity prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $articleEntity;

  /**
   * The field prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityDisplayRepository = $this->prophesize(EntityDisplayRepositoryInterface::class);
    $this->config = $this->prophesize(Config::class);
    $this->field = $this->prophesize(FieldDefinitionInterface::class);

    $this->pageEntity = $this->prophesize(ContentEntityInterface::class);
    $this->pageEntity->getEntityTypeId()->willreturn('node');
    $this->pageEntity->bundle()->willReturn('page');

    $this->articleEntity = $this->prophesize(ContentEntityInterface::class);
    $this->articleEntity->getEntityTypeId()->willreturn('node');
    $this->articleEntity->bundle()->willReturn('article');
  }

  /**
   * Tests bundle Enabled methods.
   *
   * @covers ::bundleEnabled
   * @covers ::entityBundleEnabled
   */
  public function testBundleEnabled() {
    $this->config->get("entities.node.page.migrate_override_enabled")->willReturn(TRUE);
    $this->config->get("entities.node.article.migrate_override_enabled")->willReturn(FALSE);
    $this->config->get("entities.node.nonexistingbundle.migrate_override_enabled")->willReturn(NULL);

    $service = $this->getOverrideService();

    $this->assertTrue($service->entityBundleEnabled($this->pageEntity->reveal()));
    $this->assertFalse($service->bundleEnabled('node', 'article'));
    $this->assertFalse($service->bundleEnabled('node', 'nonexistingbundle'));
  }

  /**
   * Tests Field Instance Setting Methods.
   *
   * @covers ::fieldInstanceSetting
   * @covers ::entityFieldInstanceSetting
   */
  public function testFieldInstanceSetting() {
    $this->config
      ->get("entities.node.page.fields.field_test_locked")
      ->willReturn(OverrideManagerService::FIELD_LOCKED);
    $this->config
      ->get("entities.node.page.fields.field_test_overrideable")
      ->willReturn(OverrideManagerService::FIELD_OVERRIDEABLE);
    $this->config
      ->get("entities.node.page.fields.field_test_ignored")
      ->willReturn(OverrideManagerService::FIELD_IGNORED);
    $this->config
      ->get("entities.node.page.fields.field_nonexistent")
      ->willReturn(NULL);

    $this->field->getName()->willReturn('field_test_overrideable');

    $service = $this->getOverrideService();

    $this->assertSame(OverrideManagerService::FIELD_OVERRIDEABLE,
      $service->entityFieldInstanceSetting($this->pageEntity->reveal(), $this->field->reveal()));
    $this->assertSame(OverrideManagerService::FIELD_IGNORED,
      $service->fieldInstanceSetting('node', 'page', 'field_test_ignored'));
    $this->assertSame(OverrideManagerService::FIELD_LOCKED,
      $service->fieldInstanceSetting('node', 'page', 'field_test_locked'));
    $this->assertSame(OverrideManagerService::FIELD_IGNORED,
      $service->fieldInstanceSetting('node', 'page', 'field_nonexistent'));
  }

  /**
   * Tests the getOverrideableEntityFields function.
   *
   * @covers ::getOverridableEntityFields
   * @covers ::getOverridableFields
   */
  public function testGetOverrideableFields() {

    $this->config->get('entities.node.page.migrate_override_enabled')->willReturn(TRUE);
    $this->config->get('entities.node.article.migrate_override_enabled')->willReturn(NULL);

    $service = $this->getOverrideService();

    $this->assertSame([], $service->getOverridableEntityFields($this->articleEntity->reveal()));

  }

  /**
   * Tests entityHasFieldStorage.
   *
   * @covers ::entityBundleHasField
   * @covers ::entityHasFieldStorage
   */
  public function testEntityHasStorage() {
    $this->entityFieldManager->getFieldStorageDefinitions('node')->willReturn([OverrideManagerService::FIELD_NAME => []]);
    $this->entityFieldManager->getFieldStorageDefinitions('block_content')->willReturn([]);
    $this->entityFieldManager->getFieldDefinitions('node', 'page')->willReturn([OverrideManagerService::FIELD_NAME => []]);
    $this->entityFieldManager->getFieldDefinitions('node', 'article')->willReturn([]);
    $service = $this->getOverrideService();

    $this->assertFalse($service->entityBundleHasField('block_content', 'default'));
    $this->assertFalse($service->entityBundleHasField('node', 'article'));
    $this->assertTrue($service->entityBundleHasField('node', 'page'));
  }

  /**
   * Builds the Override Manager Service.
   *
   * @return \Drupal\migrate_override\OverrideManagerService
   *   The built Override Service.
   */
  protected function getOverrideService() {
    $this->configFactory->get('migrate_override.migrateoverridesettings')->willReturn($this->config->reveal());

    return new OverrideManagerService($this->configFactory->reveal(), $this->entityFieldManager->reveal(), $this->entityTypeManager->reveal(), $this->entityDisplayRepository->reveal());
  }

}
