<?php
namespace Drupal\pagarme_marketplace\Tests\Functional;
use Drupal\Core\Url;
use Drupal\Core\Path\PathValidator;
use Drupal\pagarme_marketplace\Tests\Functional\PagarmeMarketplaceTestCase;
/**
 * Tests the recipient edit functionality.
 *
 * @group pagarme_marketplace
 */
class PagarmeMarketplaceRecipientEditTestCase extends PagarmeMarketplaceTestCase {
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
      'name' => 'Recipient edit',
      'description' => 'Create a recipient and test recipient edit functionality.',
      'group' => 'Pagarme Marketplace',
    );
  }

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Checks recipient edit functionality.
   */
  public function testRecipientEdit() {
    $this->drupalLogin($this->adminUser);
    // Create a recipient.
    $recipient = $this->dataDummyRecipient();
    $this->drupalGet('admin/commerce/config/marketplace/'.$this->api_key.'/recipients/add');
    $this->submitForm($recipient, t('Save recipient'));
    $this->assertSession()->addressEquals('admin/commerce/config/marketplace/'.$this->api_key.'/recipients');
    $this->assertSession()->responseContains(t('Recipient saved successfully.'));
    //Get the saved recipient
    $recipient_saved = $this->getRecipientByDocumentNumber($recipient['document_number']);
    $create_recipient = FALSE;
    $recipient_id = NULL;
    if (!empty($recipient_saved->pagarme_id)) {
      $recipient_id = $recipient_saved->pagarme_id;
      $create_recipient = TRUE;
    }
    // Check that the recipient exists in the database.
    $this->assertTrue($create_recipient, 'Recipient found in database.');
    // Keep the same name or company name
    $recipient['legal_name'] = 'Editted Recipient';
    // Edit a recipient.
    $this->drupalGet('admin/commerce/config/marketplace/'.$this->api_key.'/recipients/edit/'.$recipient_id);
    $this->submitForm($recipient, t('Save recipient'));
    $this->assertSession()->responseContains(t('Recipient saved successfully.'));
    $recipient_updated = $this->getRecipientByDocumentNumber($recipient['document_number']);
    $updated_successfully = FALSE;
    if (!empty($recipient_updated)) {
      $updated_successfully = TRUE;
    }
    // Check if recipient was successfully updated.
    $this->assertTrue($updated_successfully, 'Recipient successfully updated.');
  }
}