<?php

namespace Drupal\Tests\cancel_button\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests cancel button functionality under various entity route conditions.
 *
 * @group cancel_button
 */
class CancelButtonEntityRoutesTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'cancel_button',
    'cancel_button_test',
    'entity_test',
  ];

  /**
   * Array of test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    foreach (['entity_test_broken_canonical', 'entity_test_broken_collection'] as $entity_type) {
      $storage = $this->container->get('entity_type.manager')
        ->getStorage($entity_type);
      // Create some dummy entity_test_missing_routes entities.
      $entity_test = $storage->create([
        'name' => $this->randomMachineName(),
      ]);
      $entity_test->save();
      $this->entities[] = $entity_test;
    }

    $permissions = [
      'administer cancel button configuration',
      'administer entity_test content',
      'view test entity',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
  }

  /**
   * Tests that the cancel button loads on entity forms without errors.
   */
  public function testEntityFormCancelButton() {
    foreach ($this->entities as $entity) {
      $uri = $entity->toUrl('edit-form');
      $this->drupalGet($uri);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->linkExists('Cancel');
    }
  }

}
