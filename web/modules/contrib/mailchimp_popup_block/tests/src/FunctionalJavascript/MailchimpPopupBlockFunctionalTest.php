<?php

namespace Drupal\Tests\mailchimp_popup_block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the Mailchimp Popup Block feature functional.
 *
 * @group mailchimp_popup_block
 */
class MailchimpPopupBlockFunctionalTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'mailchimp_popup_block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('mailchimp_popup_block', [
      'label' => 'Newsletter',
      'description' => 'Intro text for the newsletter, shown before the button.',
      'button_text' => 'Subscribe',
      'mailchimp_baseurl' => 'mc.us19.list-manage.com',
      'mailchimp_uuid' => '9ac5e64aa79c90e8642517d48',
      'mailchimp_lid' => '6de1ea04cb',
    ]);
  }

  /**
   * Tests, that the trigger button opens the Mailchimp Popup.
   */
  public function testTriggerMailchimpPopup() {
    // Visit the frontpage.
    $this->drupalGet('<front>');

    // Click the trigger button.
    $this->click('.mailchimp-popup-block__trigger');

    // Check, that the Mailchimp Signup Popup was opened.
    $this->assertSession()->waitForElementVisible('css', '#PopupSignupForm_0', 5000);
    $this->assertSession()->elementExists('css', '#PopupSignupForm_0');
  }

}
