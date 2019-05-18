<?php
namespace Drupal\pagarme_marketplace\Tests\Functional;
use Drupal\pagarme_marketplace\Tests\Functional\PagarmeMarketplaceTestCase;
/**
 * Tests basic of access to splits
 *
 * @group pagarme_marketplace
 */
class PagarmeMarketplaceSplitAccessTestCase extends PagarmeMarketplaceTestCase {
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
      'name' => 'Split access',
      'description' => 'Test access split.',
      'group' => 'Pagarme Marketplace',
    );
  }

  protected function setUp() {
    parent::setUp();
  }
  /**
   * Tests access to the split list and edit.
   */
  public function testSplitAccess() {
    // Test user access with list view viewer permission.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/splits');
    $this->assertSession()->statusCodeEquals(200);
    //Test user access with add split permission.
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/splits/search-product');
    $this->assertSession()->statusCodeEquals(200);
    // Test that anonymous users can access the contact form.
    $this->drupalLogout();
    // Test denied access for users without permission to view list of splits.
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/splits');
    $this->assertSession()->statusCodeEquals(403);
    // Tests access denied for users without permission to add splits.
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/splits/search-product');
    $this->assertSession()->statusCodeEquals(403);
  }  
}