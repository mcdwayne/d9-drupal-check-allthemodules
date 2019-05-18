<?php

namespace Drupal\Tests\encrypt_content_client\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests functionality of adding and editing encrypted nodes 
 * using encrypt_content_client module.
 *
 * @group encrypt_content_client
 */
class EncryptionPolicyTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['encrypt_content_client'];
  protected $profile = 'standard';
  
 /**
   * Access client encryption policy config page without the right permissions.
   */
  public function testAccessSettingsPageAccessDenied() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $this->drupalGet('client_encryption/policies');
    $this->assertResponse(403);
  }
 
 /**
   * Access client encryption policy config page.
   */
  public function testAccessSettingsPage() {
    $user = $this->drupalCreateUser(["encrypt content client settings"]);
    $this->drupalLogin($user);

    $this->drupalGet('client_encryption/policies');
    $this->assertResponse(200);
  }
  
 /**
   * See if article node is avaiable from the options dropdown.
   */
  public function testCheckIfArticleOptionExists() {
    $user = $this->drupalCreateUser(["encrypt content client settings"]);
    $this->drupalLogin($user);
    
    $this->drupalGet('client_encryption/policies');
    $select_list = $this->getOptions("edit-node"); 
    $this->assertNotNull($select_list['article']);
  }
}
