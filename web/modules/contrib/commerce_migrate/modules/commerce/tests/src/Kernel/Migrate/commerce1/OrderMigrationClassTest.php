<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

/**
 * Tests the order migration plugin class.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class OrderMigrationClassTest extends Commerce1TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_store'];

  /**
   * Tests d6_profile_values builder.
   *
   * Ensures profile fields are merged into the d6_profile_values migration's
   * process pipeline.
   */
  public function testClass() {
    $migration = $this->getMigration('commerce1_order');
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
    $this->assertSame('commerce1_order', $migration->id());
    $process = $migration->getProcess();
    // Line items.
    $this->assertSame('commerce_line_items', $process['order_items'][0]['source']);
    // Order total.
    $this->assertSame('commerce_order_total', $process['total_price'][0]['source']);
    // Customer billing.
    $this->assertSame('commerce_customer_billing', $process['billing_profile'][0]['source']);
  }

}
