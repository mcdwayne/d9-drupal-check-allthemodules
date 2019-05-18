<?php

namespace Drupal\Tests\encrypt_content_client\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of encrypt_content_client module's custom REST resources.
 *
 * @group encrypt_content_client
 */
class RestResourceTest extends BrowserTestBase {

  public static $modules = [
    'rest',
    'encrypt_content_client',
  ];

  protected $profile = 'standard';
  protected $user;

  /**
   * Set up this test case.
   */
  public function setUp() {
    $this->strictConfigSchema = FALSE;
    parent::setUp();
  }

  /**
   * Tests retrieving all users public keys through REST - without the right permissions.
   */
  public function testGetAllUsersKeysNoPermission() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $this->drupalGet('client_encryption/all', ['query' => ['_format' => 'json']], ['Content-type: application/json']);
    $this->assertResponse(403);
  }

  /**
   * Tests retrieving all users public keys through REST - with the right permissions.
   */
  public function testGetAllUsersKeysWithPermission() {
    $user = $this->drupalCreateUser(['restful get ecc_key_resource']);
    $this->drupalLogin($user);
    $user->field_public_key->value = "TEST_ECC_KEY";
    $user->save();
    
    $res = $this->drupalGet('client_encryption/all', ['query' => ['_format' => 'json']], ['Content-type: application/json']);
    $this->assertResponse(200);
    $this->assertContains("TEST_ECC_KEY", $res);
  }

  /**
   * Delete an user's public key from the database.
   */
  //public function testDeleteKey() {
    /*
    $this->user->field_public_key->value = "TEST_ECC_KEY";
    
    $this->drupalPost('/client_encryption/update?_format=json', ['_method' => 'DELETE']);
    $this->assertSession()->statusCodeEquals(200);
    
    $this->assertNull($this->user->field_public_key->value, "Public key has been successfuly deleted.");
    */
  //}

  /**
   * Update encryption policy for article.
   */ 
  /*public function testUpdateEncryptionPolicySettings() {
    $user = $this->drupalCreateUser(["encrypt content client settings"]);
    $this->drupalLogin($user);  
    
    $update = [];
    $update['fields[]'] = ['title', 'body'];  
    $this->drupalPost('client_encryption/policy/article', $update, t('Save configuration'));
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/screen.png');
    $this->assertResponse(200);
  }*/
}
