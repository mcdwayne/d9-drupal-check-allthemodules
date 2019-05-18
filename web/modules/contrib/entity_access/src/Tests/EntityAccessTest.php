<?php

namespace Drupal\entity_access\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class EntityAccessTest.
 *
 * @group entity_access
 */
class EntityAccessTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'entity_access_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->createUser(['administer content types', 'bypass node access']));
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests access to the entity with specified bundle condition.
   */
  public function testEntityBundleAccess() {
    foreach ([
      'page' => 403,
      'article' => 200,
    ] as $node_type => $response_code) {
      $is_local_task_available = 200 === $response_code;

      foreach ([
        'admin/structure/types/manage' => $this->drupalCreateContentType(['type' => $node_type]),
        'node' => $this->createNode(['type' => $node_type]),
      ] as $path => $entity) {
        $path .= '/' . $entity->id();

        // Visit the page.
        $this->drupalGet($path);
        $this->assertResponse(200);

        // Check existence of local task's tab: it have not be on
        // the page if page is not accessible.
        $this->{$is_local_task_available ? 'assertLink' : 'assertNoLink'}('Test task');

        // Check access to the tab's page.
        $this->drupalGet("$path/test");
        $this->assertResponse($response_code);

        if ($is_local_task_available) {
          $this->assertRaw(t('Local task for "@node_type" content type.', [
            '@node_type' => 'node_type',
          ]));

          $this->drupalPostForm(NULL, ['test_field' => 'Test value'], t('Go'));
          $this->drupalPostForm(NULL, [], t('Confirm'));

          $this->assertRaw(t('You have successfully submitted the value: @value', [
            '@value' => 'Test value',
          ]));
        }
      }
    }
  }

}
