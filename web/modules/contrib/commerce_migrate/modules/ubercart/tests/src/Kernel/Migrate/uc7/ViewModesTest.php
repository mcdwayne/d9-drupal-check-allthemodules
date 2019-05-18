<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityViewModeInterface;

/**
 * Tests migration of Ubercart 7 view modes.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ViewModesTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'image',
    'node',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d7_view_modes');
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
    $this->assertEntity('comment.full', 'Full', 'comment');

    $this->assertEntity('node.teaser', 'Teaser', 'node');
    $this->assertEntity('node.full', 'Full', 'node');

    $this->assertEntity('commerce_product.full', 'Full', 'commerce_product');
    $this->assertEntity('commerce_product.teaser', 'Teaser', 'commerce_product');

    $this->assertEntity('taxonomy_term.full', 'Full', 'taxonomy_term');
  }

}
