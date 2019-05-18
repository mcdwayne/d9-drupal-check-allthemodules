<?php

namespace Drupal\tmgmt_oht\Tests;

use Drupal;
use Drupal\tmgmt\Tests\TMGMTTestBase;
use Drupal\tmgmt\Entity\Translator;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\RemoteMapping;

/**
 * Tests the Oht translator plugin integration.
 *
 * @group tmgmt_oht
 */
class OhtTest extends TMGMTTestBase {

  /**
   * @var \Drupal\tmgmt\Entity\Translator $translator
   */
  private $translator;

  public static $modules = array(
    'tmgmt_oht',
    'tmgmt_oht_test',
  );

  public function setUp() {
    parent::setUp();
    $this->addLanguage('de');
    $this->translator = Translator::load('oht');
    \Drupal::configFactory()->getEditable('tmgmt_oht.settings')->set('use_mock_service', TRUE)->save();
  }

  /**
   * Tests basic API methods of the plugin.
   */
  public function testAPI() {
    $job = $this->createJob();
    $job->translator = $this->translator->id();
    \Drupal::state()->set('tmgmt.test_source_data',  array(
      'wrapper' => array(
        '#text' => 'Hello world',
        '#label' => 'Wrapper label',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    // The translator should not be available at this point because we didn't
    // define an API key yet.
    $this->assertFalse($job->canRequestTranslation()->getSuccess());

    $this->translator = $job->getTranslator();

    // Test that translation requests are rejected due to a wrong API key.
    $this->translator->setSetting('api_public_key', 'wrong key');
    $this->translator->setSetting('api_secret_key', 'wrong key');
    $this->translator->save();

    $this->translator->clearLanguageCache();

    $this->assertFalse($job->canRequestTranslation()->getSuccess());

    // Save a correct api key.
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_secret_key', 'correct key');
    $this->translator->save();
    $this->assertTrue($job->canRequestTranslation()->getSuccess());

    $this->translator->clearLanguageCache();

    // Create a new job.
    $job = $this->createJob();
    $job->translator = $this->translator->id();
    $job->addItem('test_source', 'test', '1');

    // Make sure the translator returns the correct supported target languages.
    $languages = $job->getTranslator()->getSupportedTargetLanguages('en');
    $this->assertTrue(isset($languages['de']));
    $this->assertTrue(isset($languages['es']));
    $this->assertFalse(isset($languages['it']));
    $this->assertFalse(isset($languages['en']));

    // Request translation and verify that the state of the job and job item is
    // correctly updated.
    $job->requestTranslation();
    $this->assertTrue($job->isActive());
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isActive());
    }

    // Verify that the xliff file submitted is correct.
    $xliff_content = \Drupal::state()->get('tmgmt_oht_test_xliff_file_content');
    $this->assertTrue(strpos($xliff_content, 'source-language="en-us" target-language="de-de"'));
    $this->assertTrue(strpos($xliff_content, '<source xml:lang="en-us"><![CDATA[Hello world]]></source>'));

    // Retrieve the resource uuid and project id.
    $resource_uuid = \Drupal::state()->get('tmgmt_oht_test_source_resource_uuid');
    $project_id = \Drupal::state()->get('tmgmt_oht_test_project_id');

    // Create a Oht response of a completed job.
    $post = [
      'event' => 'project.resources.new',
      'project_id' => $project_id,
      'project_status_code' => 'completed',
      'resource_uuid' => $resource_uuid,
      'resource_type' => 'translation',
      'custom0' => \Drupal::state()->get('tmgmt_oht_test_tjiid'),
      'custom1' => \Drupal::state()->get('tmgmt_oht_test_tjiid_hash'),
    ];

    $url = Url::fromRoute('tmgmt_oht.callback')->setOptions(array('absolute' => TRUE))->toString();
    $response = $this->curlExec(array(CURLOPT_URL => $url, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post));

    // Response should be empty if everything went ok.
    $this->assertResponse(200);
    $this->assertTrue(empty($response));

    // Clear job item caches.
    \Drupal::entityManager()->getStorage('tmgmt_job_item')->resetCache();

    // Now it should be needs review.
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isNeedsReview());
    }

    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    $this->assertEqual('Hallo Wort', $data['wrapper']['#translation']['#text']);
  }

  /**
   * Test that the checkout form contains the correct expertise levels and
   * displays the correct quote and balance.
   */
  public function testOhtCheckoutForm() {
    $this->loginAsAdmin();
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_secret_key', 'correct key');
    $this->translator->save();

    // Create a new job.
    $job = $this->createJob();
    $job->translator = $this->translator->id();
    $job->save();
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $mocked_quote = \Drupal::state()->get('tmgmt_oht_test_quote_total');
    $mocked_expertise = \Drupal::state()->get('tmgmt_oht_test_quote_total');

    // Verify that the expertise dropdown menu contains expertise returned
    // by the mock server and that the default selected one is "-".
    $expertise = $this->xpath('//select[@id="edit-settings-expertise"]/option');
    $this->assertEqual($expertise[0]['value'], '');
    $this->assertTrue($expertise[0]['selected']);
    $this->assertOption('edit-settings-expertise', 'automotive-aerospace');
    $this->assertOption('edit-settings-expertise', 'business-finance');

    // The quote is returned as a label containing the number of words, the
    // number of credits to charge, the total price and the currency, each of
    // these are in a separate strong markup.
    $quote = $this->xpath('//div[@id="edit-settings-price-quote"]/strong');
    $this->assertEqual($quote[0], $mocked_quote->wordcount);
    $this->assertEqual($quote[1], $mocked_quote->credits);
    $this->assertEqual($quote[2], $mocked_quote->price . '€');
  }

  /**
   * Test that translations can be correctly pulled.
   */
  public function testTranslationsPulling() {
    $this->loginAsAdmin();
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_secret_key', 'correct key');
    $this->translator->save();
    $job = $this->createJob();
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'body' => array(
        '#text' => 'Hello world',
        '#label' => 'Body',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');

    $job->requestTranslation();

    // Pull translations from Oht.
    $this->drupalPostForm('admin/tmgmt/jobs/' . $job->id(), array(), t('Pull translations'));
    $this->assertText(t('The translation of test_source:test:1 to German is finished and can now be reviewed.'));
    $this->assertText(t('Fetched translations for 1 job items.'));

    // Check the updated mappings of the job item.
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id());
    $this->assertEqual(count($remotes), 1);
    $remote = reset($remotes);
    $this->assertEqual($remote->getRemoteIdentifier1(), \Drupal::state()->get('tmgmt_oht_test_project_id'));
    $this->assertEqual($remote->getRemoteIdentifier2(), \Drupal::state()->get('tmgmt_oht_test_source_resource_uuid'));
  }

  /**
   * Tests the UI of the plugin.
   */
  protected function testOhtUi() {
    $this->loginAsAdmin();
    $this->drupalGet('admin/tmgmt/translators/manage/oht');

    // Try to connect with invalid credentials.
    $edit = [
      'settings[api_public_key]' => 'wrong key',
      'settings[api_secret_key]' => 'wrong key',
    ];
    $this->drupalPostForm(NULL, $edit, t('Connect'));
    $this->assertText(t('The "OHT API Public key" or "OHT API Secret key" is not valid.'));

    // Test connection with valid credentials.
    $edit = [
      'settings[api_public_key]' => 'correct key',
      'settings[api_secret_key]' => 'correct key',
    ];
    $this->drupalPostForm(NULL, $edit, t('Connect'));
    $this->assertText('Successfully connected!');

    // Assert that default remote languages mappings were updated.
    $this->assertOptionSelected('edit-remote-languages-mappings-en', 'en-us');
    $this->assertOptionSelected('edit-remote-languages-mappings-de', 'de-de');

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText(t('@label configuration has been updated.', ['@label' => $this->translator->label()]));
  }

}
