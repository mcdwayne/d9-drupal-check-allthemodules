<?php
namespace Drupal\Tests\say_hello_dialogflow\Functional;

use Drupal\Tests\BrowserTestBase;

/**
* Testing the Dialogflow Page.
*
* @group say_hello_dialogflow
*/
class HelloWorldPageTest extends BrowserTestBase {

  /**
  * Modules to enable.
  *
  * @var array
  */
  protected static $modules = ['say_hello_dialogflow', 'user', 'node', 'redux', 'block'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->container->get('router.builder')->rebuild();
  }

  /**
  * Tests the main Hello World administer page.
  */
  public function testAdministerPage() {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/dialogflow-configuration');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('dialogflow');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout($account);

    $account = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/dialogflow-configuration');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('dialogflow');
    $this->assertSession()->statusCodeEquals(403);

  }
}