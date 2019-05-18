<?php

namespace Drupal\Tests\encrypt_content_client\FunctionalJavascript;

use Drupal\user\Entity\User;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests ECC keys generation in encrypt_content_client module.
 *
 * @group encrypt_content_client
 */
class EccKeysGenerationTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['encrypt_content_client'];
  protected $profile = 'standard';
  
  /**
   * Tests ECC keys generation without the right permissions.
   */
  public function testKeysGenerationWithoutPermissions() {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $this->drupalGet('user/ecc');
    $this->assertResponse(403);
  }
  
  /**
   * Tests ECC keys generation with the right permissions.
   */
  public function testKeysGeneration() {
    $user = $this->drupalCreateUser(['encrypt content client']);
    $this->drupalLogin($user);

    $this->drupalGet('user/ecc');
    $this->assertResponse(200);
    $page = $this->getSession()->getPage();
    
    $generate_button = $page->find('css', '#generate-ecc-keys');
    $generate_button->click();
    
    $success_message = $page->find('css', '#ecc-keys-generation-success');
    $this->assertTrue($success_message->isVisible());
  }
}
