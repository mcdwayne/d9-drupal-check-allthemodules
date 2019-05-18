<?php

namespace Drupal\Tests\private_entity\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests access on Entity Test.
 *
 * @group private_entity
 */
class PrivateEntityAccessTest extends PrivateEntityTestBase {

  /**
   * A user with permission to view private entities and nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privateViewUser;

  /**
   * A user with permission to view public entities and nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $publicViewUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      // Node type permission.
      'access content',
      'administer nodes',
      'administer content types',
      // Entity test permission.
      'view test entity',
      'administer entity_test content',
      // Private entity permission.
      'private entity view access',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->privateViewUser = $this->drupalCreateUser([
      'access content',
      'view test entity',
      'private entity view access',
    ]);

    $this->publicViewUser = $this->drupalCreateUser([
      'access content',
      'view test entity',
    ]);

    $nodeType = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $nodeType->save();
    $this->container->get('router.builder')->rebuild();

    $this->attachField('node', 'article');
    $this->attachField('entity_test', 'entity_test');
  }

  /**
   * Tests view access permissions to node.
   *
   * @todo needs work
   */
  public function testNodeViewAccess() {
    $publicNode = $this->createNode([
      'title' => 'This is public',
      "{$this->fieldName}[0]['value']" => 0,
      'status' => 1,
      'type' => 'article',
    ]);
    $publicNode->save();
    $privateNode = $this->createNode([
      'title' => 'This is private',
      "{$this->fieldName}[0]['value']" => 1,
      'status' => 1,
      'type' => 'article',
    ]);
    $privateNode->save();

    // Make sure the private_entity field is in the output.
    $this->drupalGet('node/' . $publicNode->id());
    $fields = $this->xpath('//div[contains(@class, "field--type-private-entity")]');
    // @todo returns 0
    $this->assertEquals(1, count($fields));
    $this->drupalGet('node/' . $privateNode->id());
    $fields = $this->xpath('//div[contains(@class, "field--type-private-entity")]');
    $this->assertEquals(1, count($fields));
    // Logout adminUser.
    $this->drupalLogout();

    // View as privateViewUser.
    $this->drupalLogin($this->privateViewUser);
    $this->drupalGet('node/' . $publicNode->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/' . $privateNode->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();

    // View as anonymous.
    $this->drupalGet('node/' . $publicNode->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/' . $privateNode->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();
  }

  /**
   * Tests view access permissions to entity_test.
   *
   * @todo needs work
   */
  public function testViewAccess() {
    $publicData = [
      'type' => 'entity_test',
      'name' => $this->randomMachineName(),
      $this->fieldName => 0,
    ];
    $privateData = [
      'type' => 'entity_test',
      'name' => $this->randomMachineName(),
      $this->fieldName => 1,
    ];
    $publicEntity = EntityTest::create($publicData);
    $privateEntity = EntityTest::create($privateData);
    $publicEntity->set($this->fieldName, 0);
    $privateEntity->set($this->fieldName, 1);
    $publicEntity->save();
    $privateEntity->save();

    // Tests user that has the permission to view private entities.
    $this->drupalLogout();
    $this->drupalLogin($this->privateViewUser);
    $this->drupalGet('entity_test/' . $publicEntity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('entity_test/' . $privateEntity->id());
    $this->assertSession()->statusCodeEquals(200);

    // Tests user that has the permission to view public entities.
    $this->drupalLogout();
    $this->drupalLogin($this->publicViewUser);
    $this->drupalGet('entity_test/' . $publicEntity->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('entity_test/' . $privateEntity->id());
    // @todo returns 200
    $this->assertSession()->statusCodeEquals(403);
  }

}
