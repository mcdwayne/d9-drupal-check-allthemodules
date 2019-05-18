<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Tests field migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class FieldTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'datetime',
    'file',
    'image',
    'link',
    'migrate_plus',
    'node',
    'path',
    'profile',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->executeMigrations([
      'commerce1_product_variation_type',
      'commerce1_product_type',
      'd7_field',
    ]);
  }

  /**
   * Asserts various aspects of a field_storage_config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.FIELD_NAME.
   * @param string $type
   *   The expected field type.
   * @param bool $translatable
   *   Whether or not the field is expected to be translatable.
   * @param int $cardinality
   *   The expected cardinality of the field.
   * @param array $dependencies
   *   The field's dependencies.
   */
  protected function assertEntity($id, $type, $translatable, $cardinality, array $dependencies) {
    list ($entity_type) = explode('.', $id);

    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field = FieldStorageConfig::load($id);
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $this->assertSame($type, $field->getType());
    $this->assertEquals($translatable, $field->isTranslatable());
    $this->assertSame($entity_type, $field->getTargetEntityTypeId());
    $this->assertSame($dependencies, $field->getDependencies());
    if ($cardinality === 1) {
      $this->assertFalse($field->isMultiple());
    }
    else {
      $this->assertTrue($field->isMultiple());
    }
    $this->assertSame($cardinality, $field->getCardinality());
  }

  /**
   * Test field migration from Drupal 7 to Drupal 8.
   */
  public function testField() {
    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field = FieldStorageConfig::load('comment.comment_body');
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field);

    // Commerce product variation field storage.
    $field = FieldStorageConfig::load('commerce_product_variation.field_images');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product_variation.title_field');
    $this->assertNull($field);

    // The default price on product in D8 is a base field without a field
    // storage so migrating this could be skipped. However, the source product
    // may have additional price field so migrate them all.
    // @TODO find a way to not migrate the base price field storage.
    $field = FieldStorageConfig::load('commerce_product_variation.commerce_price');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);

    // Commerce product variation attribute field storage.
    $dependencies = [
      'module' => ['commerce_product'],
    ];
    $this->assertEntity('commerce_product_variation.attribute_bag_size', 'entity_reference', TRUE, 1, $dependencies);
    $this->assertEntity('commerce_product_variation.attribute_color', 'entity_reference', TRUE, 1, $dependencies);
    $this->assertEntity('commerce_product_variation.attribute_hat_size', 'entity_reference', TRUE, 1, $dependencies);
    $this->assertEntity('commerce_product_variation.attribute_shoe_size', 'entity_reference', TRUE, 1, $dependencies);
    $this->assertEntity('commerce_product_variation.attribute_storage_capacity', 'entity_reference', TRUE, 1, $dependencies);
    $this->assertEntity('commerce_product_variation.attribute_top_size', 'entity_reference', TRUE, 1, $dependencies);

    // Commerce product field storage.
    $field = FieldStorageConfig::load('commerce_product.body');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_brand');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_category');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_collection');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_gender');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_product');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.title_field');
    $this->assertNull($field);

    // Commerce product field storage should not be duplicated on nodes.
    $field = FieldStorageConfig::load('node.field_brand');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_category');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_collection');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_gender');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_product');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);

    // Node field storage.
    $field = FieldStorageConfig::load('node.field_blog_category');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.body');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.title_field');
    $this->assertNull($field);
    $field = FieldStorageConfig::load('node.field_headline');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_image');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_link');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_tags');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.field_tagline');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('node.title_field');
    $this->assertNull($field);

    // Node only field storage should not be duplicated on commerce products.
    $field = FieldStorageConfig::load('commerce_product.field_blog_category');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_headline');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_image');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_link');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_tags');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('commerce_product.field_tagline');
    $this->assertFalse($field instanceof FieldStorageConfigInterface);

    $field = FieldStorageConfig::load('taxonomy_term.field_image');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);
    $field = FieldStorageConfig::load('taxonomy_term.field_category_color');
    $this->assertTrue($field instanceof FieldStorageConfigInterface);

    // Address field storage.
    $field = FieldStorageConfig::load('profile.address');
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field);

    // Test that a rerun of the migration does not cause errors.
    $this->executeMigration('d7_field');
    $migration = $this->getMigration('d7_field');
    $errors = $migration->getIdMap()->errorCount();
    $this->assertSame(0, $errors);
  }

}
