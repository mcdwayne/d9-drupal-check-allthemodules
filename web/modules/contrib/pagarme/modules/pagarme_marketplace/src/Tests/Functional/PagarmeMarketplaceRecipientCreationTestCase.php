<?php
namespace Drupal\pagarme_marketplace\Tests\Functional;
use Drupal\pagarme_marketplace\Tests\Functional\PagarmeMarketplaceTestCase;
/**
 * Tests the creation of an recipient.
 *
 * @group pagarme_marketplace
 */
class PagarmeMarketplaceRecipientCreationTestCase extends PagarmeMarketplaceTestCase {
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
      'name' => 'Recipient creation',
      'description' => 'Create a recipient and test saving it.',
      'group' => 'Pagarme Marketplace',
    );
  }

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Creates a "Recipient" and verifies its consistency in the database.
   */
  public function testRecipientCreation() {
    $this->drupalLogin($this->adminUser);
    // Create a recipient.
    $recipient = $this->dataDummyRecipient();
    $this->drupalGet('admin/commerce/config/marketplace/'.$this->api_key.'/recipients/add');
    $this->submitForm($recipient, 'Save recipient');
    $this->assertSession()->addressEquals('admin/commerce/config/marketplace/'.$this->api_key.'/recipients');
    $this->assertSession()->responseContains(t('Recipient saved successfully.'));
  }
}