<?php
namespace Drupal\pagarme_marketplace\Tests\Functional;
use Drupal\pagarme_marketplace\Tests\Functional\PagarmeMarketplaceTestCase;
/**
 * Tests access of recipient pages.
 *
 * @group pagarme_marketplace
 */
class PagarmeMarketplaceRecipientAccessTestCase extends PagarmeMarketplaceTestCase {
  /**
   * Disable strict config schema checking.
   *
   * The schema is verified at the end of running the update.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  public static function getInfo() {
    return array(
      'name' => 'Recipient access',
      'description' => 'Test access recipient.',
      'group' => 'Pagarme Marketplace',
    );
  }

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests access to the recipient list and edit.
   */
  public function testRecipientAccess() {
    // Test user access with list view viewer permission.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/recipients');
    $this->assertSession()->statusCodeEquals(200);
    // Test user access with add recipient permission.
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/recipients/add');
    $this->assertSession()->statusCodeEquals(200);
    // Test that anonymous users can access the contact form.
    $this->drupalLogout();
    // Test denied access for users without permission to view list of recipients.
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/recipients');
    $this->assertSession()->statusCodeEquals(403);
    // Tests access denied for users without permission to add recipients.
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/recipients/add');
    $this->assertSession()->statusCodeEquals(403);
  }  
}