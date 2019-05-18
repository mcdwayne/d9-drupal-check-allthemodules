<?php

namespace Drupal\Tests\encrypt_content_client\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests functionality of adding and editing encrypted nodes 
 * using encrypt_content_client module.
 *
 * @group encrypt_content_client_
 */
class NodeEncryption extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['encrypt_content_client'];
  protected $profile = 'standard';

  /**
   * Adding a node before adding client encryption policy settings.
   */
  public function testAddingNodeWithoutPolicy() {
    $user = $this->drupalCreateUser(['create article content, encrypt content client']);
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/article');
    $this->assertResponse(200);
    $page = $this->getSession()->getPage();
    $page->findField('edit-title-0-value')->setValue("Sample node title");
    $page->findField('edit-body-wrapper')->setValue("Sample body");
    $page->find('css', '#edit-submit')->click();
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/screen.png');
  }

  /**
   * Adding a node after adding client encryption policy settings.
   */
  public function testAddingNodeWithPolicy(['create article content, encrypt content client']) {
    $this->strictConfigSchema = FALSE;
    
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/article');
    $this->assertSession()->statusCodeEquals(200, "Node creation page returned 200.");
    $page = $this->getSession()->getPage();
  /
  
 /**
   * Adding a node after adding client encryption policy settings.
   */
  public function testAddingNodeWithoutPermission(['create article content, encrypt content client']) {
    $this->strictConfigSchema = FALSE;
    $user = $this->drupalCreateUser((['create article content']);
    $this->drupalLogin($user);

    $this->drupalGet('/node/add/article');
    $this->assertSession()->statusCodeEquals(200, "Node creation page returned 200.");
    $page = $this->getSession()->getPage();
  /
  
}
