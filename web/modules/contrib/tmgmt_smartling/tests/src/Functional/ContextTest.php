<?php

namespace Drupal\Tests\tmgmt_smartling\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Context tests.
 *
 * @group tmgmt_smartling
 */
class ContextTest extends SmartlingTestBase {

  use CronRunTrait;

  /**
   * Test manual context sending.
   */
  public function testManualContextSending() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->drupalPostForm('admin/tmgmt/job_items', [
        'action' => 'tmgmt_smartling_send_context',
        'tmgmt_job_item_bulk_form[0]' => 'WyJ1bmQiLCIxIl0=',
      ], t('Apply to selected items'), [
        'query' => [
          'state' => 'All',
          'source_language' => 'All',
          'target_language' => 'All',
        ],
      ]);
      $this->drupalPostForm(NULL, [], t('Send Context to Smartling'));
      $this->drupalGet('admin/reports/dblog');
      $this->assertRaw('We are about to switch user');
      $this->assertRaw('User was successfully switched');
      $this->assertRaw(t('Context upload for file @filename completed successfully.', ['@filename' => $fileName]));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test context sending by cron.
   */
  public function testContextSendingByCron() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->cronRun();
      $this->drupalGet('admin/reports/dblog');
      $this->assertRaw('We are about to switch user');
      $this->assertRaw('User was successfully switched');
      $this->assertRaw(t('Context upload for file @filename completed successfully.', ['@filename' => $fileName]));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test Smartling context debugger: show context.
   */
  public function testSmartlingContextDebuggerShowContext() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      global $base_url;

      $this->drupalPostForm('admin/tmgmt/smartling-context-debug', [
        'do_direct_output' => TRUE,
        'url' => $base_url . '/node/' . $this->testNodeId,
      ], t('Test context'));
      $this->assertText($this->testNodeTitle);
      $this->assertText($this->testNodeBody);
      $this->drupalGet('admin/reports/dblog');
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test Smartling context debugger: send context.
   */
  public function testSmartlingContextDebuggerSendContext() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      global $base_url;

      // Workaround: TmgmtSmartlingContextDebugForm::submitForm() uses global
      // config not provider config:
      // \Drupal::config('tmgmt.translator.smartling')->get('settings');
      $this->drupalPostForm('admin/tmgmt/translators/manage/smartling', $this->smartlingPluginProviderSettings, t('Save'));

      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->checkGeneratedFile($fileName, $this->testNodeTitle);

      $this->drupalPostForm('admin/tmgmt/smartling-context-debug', [
        'do_direct_output' => FALSE,
        'filename' => $fileName,
        'url' => $base_url . '/node/' . $this->testNodeId,
      ], t('Test context'));
      $this->assertNoText($this->testNodeTitle);
      $this->assertNoText($this->testNodeBody);
      $this->assertText('Smartling response');
      $this->drupalGet('admin/reports/dblog');
      $this->assertRaw(t('Context upload for file @filename completed successfully.', ['@filename' => $fileName]));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test context uploading with turned off "Silent switching mode" feature.
   */
  public function testSilentSwitchingOff() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->cronRun();
      $this->drupalGet('admin/reports/dblog');
      $this->assertRaw('We are about to switch user');
      $this->assertRaw(t('User @name has logged out.', [
        '@name' => 'Anonymous',
      ]));
      $this->assertRaw(t('User @name has logged in.', [
        '@name' => $this->smartlingPluginProviderSettings['settings[contextUsername]'],
      ]));
      $this->assertRaw('User was successfully switched');
      $this->assertRaw(t('Context upload for file @filename completed successfully.', ['@filename' => $fileName]));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test context uploading with turned on "Silent switching mode" feature.
   */
  public function testSilentSwitchingOn() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      // Set up silent user switching mode.
      $plugin_settings = $this->smartlingPluginProviderSettings;
      $plugin_settings['settings[context_silent_user_switching]'] = TRUE;

      $translator = $this->setUpSmartlingProviderSettings($plugin_settings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->cronRun();
      $this->drupalGet('admin/reports/dblog');
      $this->assertRaw('We are about to switch user');
      $this->assertNoRaw(t('User @name has logged out.', [
        '@name' => 'Anonymous',
      ]));
      $this->assertNoRaw(t('User @name has logged in.', [
        '@name' => $this->smartlingPluginProviderSettings['settings[contextUsername]'],
      ]));
      $this->assertRaw('User was successfully switched');
      $this->assertRaw(t('Context upload for file @filename completed successfully.', ['@filename' => $fileName]));
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  /**
   * Test override context css class.
   *
   * We must not set "sl-override-context" class for context because it causes
   * huge load on context service.
   */
  public function testOverrideContextOption() {
    if (!empty($this->smartlingPluginProviderSettings)) {
      $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
      $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
      $fileName = $job->getTranslatorPlugin()->getFileName($job);
      $this->drupalPostForm('admin/tmgmt/job_items', [
        'action' => 'tmgmt_smartling_send_context',
        'tmgmt_job_item_bulk_form[0]' => 'WyJ1bmQiLCIxIl0=',
      ], t('Apply to selected items'), [
        'query' => [
          'state' => 'All',
          'source_language' => 'All',
          'target_language' => 'All',
        ],
      ]);
      $this->drupalPostForm(NULL, [], t('Send Context to Smartling'));

      $fileName = str_replace('.', '_', $fileName);
      $file_path = \Drupal::getContainer()->get('file_system')->realpath(file_default_scheme() . "://tmgmt_smartling_context/{$fileName}.html");
      $content = file_get_contents($file_path);

      $this->assertTrue(strpos($content, 'class="sl-override-context"') === FALSE);
    }
    else {
      $this->fail("Smartling settings file for simpletests not found.");
    }
  }

  // TODO: fix this test.
  // For some reason it fails with "Raw "We are about to switch user from &quot;igh4bsl7&quot; to &quot;admin&quot;" found"
  // message.
  // /**
  //  * Test context user switch back.
  //  */
  // public function testContextUserSwitchBack() {
  //   if (!empty($this->smartlingPluginProviderSettings)) {
  //     $currentUserName = $user = \Drupal::currentUser()->getAccountName();
  //     $contextUserName = $this->smartlingPluginProviderSettings['settings[contextUsername]'];
  //     $translator = $this->setUpSmartlingProviderSettings($this->smartlingPluginProviderSettings);
  //     $job = $this->requestTranslationForNode($this->testNodeId, $this->targetLanguage, $translator);
  //     $fileName = $job->getTranslatorPlugin()->getFileName($job);
  //     $this->drupalPostForm('admin/tmgmt/job_items', [
  //       'action' => 'tmgmt_smartling_send_context',
  //       'tmgmt_job_item_bulk_form[0]' => 'WyJ1bmQiLCIxIl0=',
  //     ], t('Apply to selected items'), [
  //       'query' => [
  //         'state' => 'All',
  //         'source_language' => 'All',
  //         'target_language' => 'All',
  //       ],
  //     ]);
  //     $this->drupalPostForm(NULL, [], t('Send Context to Smartling'));
  //     $this->drupalGet('admin/reports/dblog');
  //
  //     // Switched from current to context user.
  //     $this->assertRaw(t('We are about to switch user from "@currentUserName" to "@contextUserName"', [
  //       '@currentUserName' => $currentUserName,
  //       '@contextUserName' => $contextUserName,
  //     ]));
  //     $this->assertRaw(t('User was successfully switched to "@contextUserName"', [
  //       '@contextUserName' => $contextUserName,
  //     ]));
  //
  //     $this->assertRaw(t('Context upload for file @filename completed successfully.', ['@filename' => $fileName]));
  //
  //     // Switched back from context to initial user.
  //     $this->assertRaw(t('We are about to switch user from "@contextUserName" to "@currentUserName"', [
  //       '@contextUserName' => $contextUserName,
  //       '@currentUserName' => $currentUserName,
  //     ]));
  //     $this->assertRaw(t('User was successfully switched to "@currentUserName"', [
  //       '@currentUserName' => $currentUserName,
  //     ]));
  //   }
  //   else {
  //     $this->fail("Smartling settings file for simpletests not found.");
  //   }
  // }
}
