<?php

namespace Drupal\akismet\Tests;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Tests module installation and key error handling.
 * @group akismet
 */
class InstallationTest extends AkismetTestBase {
  public static $modules = ['dblog', 'node', 'comment'];

  protected $useLocal = TRUE;
  public $disableDefaultSetup = TRUE;
  protected $createKeys = FALSE;
  protected $setupAkismet = FALSE;

  protected $webUser = NULL;

  function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer modules',
      'administer permissions',
    ]);
    $this->webUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests status handling after installation.
   *
   * We walk through a regular installation of the Akismet module instead of using
   * setUp() to ensure that everything works as expected.
   *
   * Note: Partial error messages tested here; hence, no t().
   */
  function testInstallationProcess() {
    $message_short_invalid = t('The configured Akismet API keys are invalid.');
    $message_invalid = t('The Akismet servers could be contacted, but Akismet API keys could not be verified.');
    $message_valid = t('The services are operating correctly.');
    $message_missing = t('The Akismet API key is not configured yet.');
    $message_server = t('The Akismet servers could not be contacted. Please make sure that your web server can make outgoing HTTP requests.');
    $message_saved = t('The configuration options have been saved.');
    $admin_message = t('Visit the Akismet settings page to configure your keys.');
    $install_message = t('Akismet installed successfully. Visit the @link to configure your keys.', [
      '@link' => t('Akismet settings page'),
    ]);

    $this->drupalLogin($this->adminUser);

    // Ensure there is no requirements error by default.
    $this->drupalGet('admin/reports/status');
    $this->clickLink('run cron manually');

    // Install the Akismet module.
    $this->drupalPostForm('admin/modules', ['modules[Acquia][akismet][enable]' => TRUE], t('Install'));
    $this->assertText($install_message);

    // Now we can add the test module for the rest of the form tests.
    \Drupal::service('module_installer')->install(['akismet_test', 'akismet_test_server']);
    $settings = \Drupal::configFactory()->getEditable('akismet.settings');
    $settings->set('log_level', RfcLogLevel::DEBUG);
    $settings->save();

    $this->resetAll();

    // Verify that forms can be submitted without valid Akismet module configuration.
    $this->drupalLogin($this->webUser);
    $edit = array(
      'title' => 'spam',
    );
    $this->drupalPostForm('akismet-test/form', $edit, t('Save'));
    $this->assertText('Successful form submission.');

    // Assign the 'administer akismet' permission and log in a user.
    $this->drupalLogin($this->adminUser);
    $edit = array(
      \Drupal\user\RoleInterface::AUTHENTICATED_ID . '[administer akismet]' => TRUE,
    );
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Verify presence of 'empty keys' error message.
    $this->drupalGet('admin/config/content/akismet');
    $this->assertText($message_missing);
    $this->assertNoText($message_invalid);

    // Verify requirements error about missing API keys.
    $this->drupalGet('admin/reports/status');
    $this->assertText($message_missing, t('Requirements error found.'));
    $this->assertText($admin_message, t('Requirements help link found.'));

    // Configure invalid keys.
    $edit = array(
      'keys[public]' => 'the-invalid-akismet-api-key-value',
      'keys[private]' => 'the-invalid-akismet-api-key-value',
    );
    $this->drupalGet('admin/config/content/akismet/settings');
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_saved);
    $this->assertNoText(self::FALLBACK_MESSAGE, t('Fallback message not found.'));

    // Verify presence of 'incorrect keys' error message.
    $this->assertText($message_short_invalid);
    $this->assertNoText($message_missing);
    //$this->assertNoText($message_server);

    // Verify requirements error about invalid API keys.
    $this->drupalGet('admin/reports/status', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_short_invalid);

    // Ensure unreachable servers.
    \Drupal::state()->set('akismet.testing_use_local_invalid', TRUE);

    // Verify presence of 'network error' message.
    $this->drupalGet('admin/config/content/akismet/settings', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_server);
    $this->assertNoText($message_missing);
    $this->assertNoText($message_invalid);

    // Verify requirements error about network error.
    $this->drupalGet('admin/reports/status', ['watchdog' => RfcLogLevel::EMERGENCY]);
    $this->assertText($message_server);
    $this->assertNoText(self::FALLBACK_MESSAGE, t('Fallback message not found.'));

    // From here on out the watchdog errors are just a nuisance.
    $this->assertWatchdogErrors = FALSE;

    // Create a testing site on backend to have some API keys.
    \Drupal::state()->setMultiple([
      'akismet.testing_use_local' => TRUE,
      'akismet.testing_use_local_invalid' => FALSE,
    ]);
    $this->resetAll();

    $akismet = $this->getClient(TRUE);
    $akismet->createKeys();

    $response = $akismet->getSite();
    $this->assertSame('publicKey', $response['publicKey'], $akismet->publicKey);

    $edit = array(
      'keys[public]' => $akismet->publicKey,
      'keys[private]' => $akismet->privateKey,
    );
    $this->drupalPostForm('admin/config/content/akismet/settings', $edit, t('Save configuration'));
    $this->assertText($message_saved);
    $this->assertText($message_valid);
    $this->assertNoText($message_missing);
    $this->assertNoText($message_invalid);

    // Verify that deleting keys throws the correct error message again.
    $this->drupalGet('admin/config/content/akismet/settings');
    $this->assertText($message_valid);
    $edit = array(
      'keys[public]' => '',
      'keys[private]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText($message_saved);
    $this->assertNoText($message_valid);
    $this->assertText($message_missing);
    $this->assertNoText($message_invalid);
  }
}
