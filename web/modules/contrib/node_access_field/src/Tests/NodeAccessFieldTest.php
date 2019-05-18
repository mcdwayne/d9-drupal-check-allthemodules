<?php

namespace Drupal\node_access_field\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Node Access by Field test coverage.
 *
 * @group Node Access by Field
 */
class NodeAccessFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_field');

  /**
   * User accounts to test access.
   *
   * @var UserInterface[]
   */
  protected $accounts = [];

  /**
   * User accounts to test access.
   *
   * @var NodeInterface[]
   */
  protected $nodes = [];

  /**
   * Adds the users which will be utilized in this test.
   */
  public function setUp() {
    parent::setUp();

    // Create user accounts.
    $this->accounts['admin'] = $this->drupalCreateUser(['bypass node access']);
    $this->accounts['user_a'] = $this->drupalCreateUser();
    $this->accounts['user_b'] = $this->drupalCreateUser();

    // Create content type.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateContentType(['type' => 'restricted_page']);

    // Create the 'field_associated_users' field, which restricts access to
    // users to referenced by the node.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_associated_users',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => ['target_type' => 'user'],
    ));
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'restricted_page',
    ])->save();
    node_access_rebuild();

    // Create content.
    $settings = ['type' => 'page'];
    $this->nodes['page_published'] = $this->createNode($settings);
    $settings['status'] = 0;
    $this->nodes['page_unpublished'] = $this->createNode($settings);
    $settings = [
      'type' => 'restricted_page',
      'field_associated_users' => [$this->accounts['user_a']->id()],
    ];
    $this->nodes['restricted_published'] = $this->createNode($settings);
    $settings['status'] = 0;
    $this->nodes['restricted_unpublished'] = $this->createNode($settings);
  }

  /**
   * Tests access to content.
   */
  public function testAccess() {
    // Tests anonymous access.
    $this->drupalGet('node/' . $this->nodes['page_published']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['page_unpublished']->id());
    $this->assertResponse(403);
    $this->drupalGet('node/' . $this->nodes['restricted_published']->id());
    $this->assertResponse(403);
    $this->drupalGet('node/' . $this->nodes['restricted_unpublished']->id());
    $this->assertResponse(403);

    // Tests admin access.
    $this->drupalLogin($this->accounts['admin']);
    $this->drupalGet('node/' . $this->nodes['page_published']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['page_unpublished']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['restricted_published']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['restricted_unpublished']->id());
    $this->assertResponse(200);

    // User who should access the restricted content
    $this->drupalLogin($this->accounts['user_a']);
    $this->drupalGet('node/' . $this->nodes['page_published']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['page_unpublished']->id());
    $this->assertResponse(403);
    $this->drupalGet('node/' . $this->nodes['restricted_published']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['restricted_unpublished']->id());
    $this->assertResponse(403);

    // User who should not access the restricted content.
    $this->drupalLogin($this->accounts['user_b']);
    $this->drupalGet('node/' . $this->nodes['page_published']->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->nodes['page_unpublished']->id());
    $this->assertResponse(403);
    $this->drupalGet('node/' . $this->nodes['restricted_published']->id());
    $this->assertResponse(403);
    $this->drupalGet('node/' . $this->nodes['restricted_unpublished']->id());
    $this->assertResponse(403);
  }

}
