<?php

namespace Drupal\Tests\access_unpublished\Functional;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests the article creation.
 *
 * @group access_unpublished
 */
class AccessTest extends BrowserTestBase {

  use NodeCreationTrait;

  public static $modules = ['access_unpublished', 'node'];

  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access content',
      'access_unpublished node page',
    ]);

    // Create an unpublished entity.
    $this->entity = $this->createNode(['status' => FALSE]);

    $assert_session = $this->assertSession();

    // Verify that the entity is not accessible.
    $this->drupalGet($this->entity->url());
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Checks entity access before and after token creation.
   */
  public function testAccessWithValidToken() {
    $assert_session = $this->assertSession();

    // Create tokens for the entity.
    $expiredToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmExpired',
      'expire' => REQUEST_TIME - 100,
    ]);
    $expiredToken->save();
    $validToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmValid',
      'expire' => REQUEST_TIME + 100,
    ]);
    $validToken->save();

    // Verify that entity is accessible, but only with the correct hash.
    $this->drupalGet($this->entity->url('canonical'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
    $this->drupalGet($this->entity->url('canonical'), ['query' => ['auHash' => 123456]]);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet($this->entity->url());
    $assert_session->statusCodeEquals(403);

    // Delete the token.
    $validToken->delete();

    // Verify that the entity is not accessible.
    $this->drupalGet($this->entity->url('canonical'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Checks entity access before and after token creation.
   */
  public function testAccessWithExpiredToken() {
    $assert_session = $this->assertSession();

    // Create a token for the entity.
    $token = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => '12345',
      'expire' => REQUEST_TIME - 100,
    ]);
    $token->save();

    // Verify that entity is accessible, but only with the correct hash.
    $this->drupalGet($this->entity->url('canonical'), ['query' => ['auHash' => 12345]]);
    $assert_session->statusCodeEquals(403);
  }

}
