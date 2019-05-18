<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityViewModeInterface;

/**
 * Tests migration of view modes.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ViewModesTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'menu_ui',
    'migrate_plus',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateStore();
    $this->migrateFields();
  }

  /**
   * Asserts various aspects of a view mode entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $label
   *   The expected label of the view mode.
   * @param string $entity_type
   *   The expected entity type ID which owns the view mode.
   */
  protected function assertEntity($id, $label, $entity_type) {
    /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode */
    $view_mode = EntityViewMode::load($id);
    $this->assertInstanceOf(EntityViewModeInterface::class, $view_mode);
    $this->assertSame($label, $view_mode->label());
    $this->assertSame($entity_type, $view_mode->getTargetType());
  }

  /**
   * Tests migration of D6 view mode to node and commerce_product entities.
   */
  public function testMigration() {
    $this->assertEntity('node.teaser', 'Teaser', 'node');
    $this->assertEntity('node.full', 'Full content', 'node');
    $this->assertEntity('node.rss', 'RSS', 'node');

    $this->assertEntity('commerce_product.teaser', 'Teaser', 'commerce_product');
    $this->assertEntity('commerce_product.full', 'Full', 'commerce_product');
    $this->assertEntity('commerce_product.rss', 'RSS', 'commerce_product');
  }

}
