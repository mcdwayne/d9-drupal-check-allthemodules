<?php

namespace Drupal\akismet\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\akismet\Entity\FormInterface;

/**
 * Tests toggling of testing mode.
 * @group akismet
 */
class TestingModeTest extends AkismetTestBase {

  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server', 'akismet_test'];

  /**
   * Overrides AkismetWebTestCase::$akismetClass.
   *
   * In order to test toggling of the testing mode, ensure the regular class for
   * production usage is used.
   */
  protected $akismetClass = 'AkismetDrupal';

  /**
   * Prevent automated setup of testing keys.
   */
  public $disableDefaultSetup = TRUE;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  function setUp() {
    parent::setUp();
    $this->settings = \Drupal::configFactory()->getEditable('akismet.settings');
    $this->settings->set('test_mode.enabled', FALSE)->save();
    $this->getClient(TRUE);

    // Enable testing mode warnings.
    \Drupal::state()->set('akismet.omit_warning', FALSE);

    $this->adminUser = $this->drupalCreateUser(array(
      'access administration pages',
      'administer akismet',
    ));
  }

  /**
   * Tests enabling and disabling of testing mode.
   */
  function testTestingMode() {
    $this->drupalLogin($this->adminUser);

    // Protect akismet_test_form.
    $this->setProtectionUI('akismet_test_post_form', FormInterface::AKISMET_MODE_ANALYSIS);
    $this->settings->set('fallback', FormInterface::AKISMET_FALLBACK_ACCEPT)->save();

    // Setup production API keys and expected languages. They must be retained.
    $publicKey = 'the-invalid-akismet-api-key-value';
    $privateKey = 'the-invalid-akismet-api-key-value';
    $expectedLanguages = ['en','de'];
    $edit = [
      'keys[public]' => $publicKey,
      'keys[private]' => $privateKey,
      'languages_expected[]' => $expectedLanguages,
    ];
    $this->drupalGet('admin/config/content/akismet/settings');
    $this->assertText('The Akismet API key is not configured yet.');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertNoText('The Akismet API key is not configured yet.');
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertText('The configured Akismet API keys are invalid.');

    $this->drupalLogout();

    // Verify that spam can be posted, since testing mode is disabled and API
    // keys are invalid.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam',
    ];
    $this->drupalGet('akismet-test/form', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->drupalPostForm(NULL, $edit, t('Save'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText('Successful form submission.');

    // Enable testing mode.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'testing_mode' => TRUE,
    ];
    $this->drupalGet('admin/config/content/akismet/settings', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText('The configured Akismet API keys are invalid.');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertNoText('The Akismet API key is not configured yet.');
    $this->assertNoText('The configured Akismet API keys are invalid.');
    $this->assertText(t('Akismet testing mode is still enabled.'));
    // Verify that expected languages were retained.
    foreach($expectedLanguages as $lang) {
      $this->assertOptionSelected('edit-languages-expected', $lang);
    }

    $this->drupalLogout();

    // Verify presence of testing mode warning.
    $this->drupalGet('akismet-test/form');
    /*
     * There is a problem with the way #lazy_builder is handling the status
     * messages in tests.  As a result, the text is only output when a message
     * is set before it too.... further investigation is needed and possibly
     * a bug filed.
    $this->assertText(t('Akismet testing mode is still enabled.'));
    */

    // Verify that no spam can be posted with testing mode enabled.
    $edit = [
      'title' => $this->randomString(),
      'body' => 'spam',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(self::SPAM_MESSAGE);
    $this->assertNoText('Successful form submission.');

    // Disable testing mode.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/akismet/settings');
    $this->assertText('Akismet testing mode is still enabled.');
    $edit = [
      'testing_mode' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertText('The configured Akismet API keys are invalid.');
    $this->assertNoText('Akismet testing mode is still enabled.');

    // Verify that production API keys still exist.
    $this->assertFieldByName('keys[public]', $publicKey);
    $this->assertFieldByName('keys[private]', $privateKey);
    foreach($expectedLanguages as $lang) {
      $this->assertOptionSelected('edit-languages-expected', $lang);
    }
  }
}
