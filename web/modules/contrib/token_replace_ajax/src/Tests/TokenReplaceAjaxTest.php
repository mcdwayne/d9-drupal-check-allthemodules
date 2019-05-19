<?php

/**
 * @file
 * Contains \Drupal\token_replace_ajax\Tests\TokenReplaceAjaxTest.
 */

namespace Drupal\token_replace_ajax\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\token_replace_ajax\Controller\TokenReplaceAjaxController;

/**
 * Tests general Token replace AJAX functionality.
 *
 * @group Token replace AJAX
 */
class TokenReplaceAjaxTest extends WebTestBase {

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authUser;

  /**
   * A content type name.
   *
   * @var string.
   */
  public $contentType;

  /**
   * An authenticated content creation user.
   *
   * @var \Drupal\user\UserInterface
   */
  public $contentUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'token_replace_ajax_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a content type.
    $this->contentType = $this->drupalCreateContentType()->id();

    // Create authenticated users.
    $this->authUser = $this->drupalCreateUser([
      'access token_replace_ajax callback',
      "create {$this->contentType} content",
    ]);
    $this->contentUser = $this->drupalCreateUser([
      "create {$this->contentType} content",
    ]);
  }

  /**
   * Test basic anonymous usage.
   */
  public function testAnon() {
    // Ensure an unauthenticated anonymous user can't access Token replace AJAX.
    $this->drupalGet('token_replace/ajax/[site:name]');
    $this->assertText('Access denied', t('Anonymous user can not access Token replace AJAX.'));

    // Generate authentication key.
    $auth_key = TokenReplaceAjaxController::getAccessToken('[site:name]');

    // Ensure that authenticated anonymous user can access Token replace AJAX.
    $query = ['key' => $auth_key];
    $json = $this->drupalGetAJAX('token_replace/ajax/[site:name]', ['query' => $query]);
    $this->assertNoText('Access denied', t('Anonymous user can access Token replace AJAX with authorisation key.'));

    // Ensure that the response from '[site:name]' contained the correctly
    // replaced token value.
    $this->assertEqual($json['value'], \Drupal::config('system.site')
      ->get('name'), t('The response contained a correctly replaced token value.'));
  }

  /**
   * Test authenticated usage.
   */
  public function testAuth() {
    // Login as authenticated user.
    $this->drupalLogin($this->authUser);

    // Ensure that the response from '[site:name]' contained the correctly
    // replaced token value.
    $json = $this->drupalGetAJAX('token_replace/ajax/[site:name]');
    $this->assertEqual($json['value'], \Drupal::config('system.site')
      ->get('name'), t('The response contained a correctly replaced token value.'));

    // Create a node.
    $node = $this->drupalCreateNode(['type' => $this->contentType]);

    // Ensure that the response from '[node:title]' contained the correctly
    // replaced token value.
    $query = ['entity_type' => 'node', 'entity_id' => $node->id()];
    $json = $this->drupalGetAJAX('token_replace/ajax/[node:title]', ['query' => $query]);
    $this->assertEqual($json['value'], $node->getTitle(), t('The response contained a correctly replaced token value.'));
  }

  /**
   * Test form post usage.
   */
//  public function testFormPost() {
//    // Login as content user.
//    $this->drupalLogin($this->contentUser);
//
//    // Post an unauthorized AJAX request from a node form.
//    $query = ['token_replace_ajax_test' => FALSE];
//    $this->drupalGet("node/add/{$this->contentType}", ['query' => $query]);
//    $edit = ['title[0][value]' => $this->randomMachineName()];
//    $json = $this->drupalPostAJAXForm(NULL, $edit, 'token_replace_ajax', "node/add/{$this->contentType}?ajax_form=1&token_replace_ajax=[node:title]");
//    $this->assertTrue(is_null($json), t('User can not access Token replace AJAX via unauthorized form post.'));
//
//    // Post an authorized AJAX request from a node form.
//    $query = ['token_replace_ajax_test' => TRUE];
//    $this->drupalGet("node/add/{$this->content_type}", ['query' => $query]);
//    $json = $this->drupalPostAJAX(NULL, $edit, [], 'token_replace/ajax/[node:title]', [], [], NULL, []);
//
//    // Ensure that the response from '[node:title]' contained the correctly
//    // replaced token value.
//    $this->assertEqual($json['value'], $edit['title'], t('The response contained a correctly replaced token value.'));
//  }

}
