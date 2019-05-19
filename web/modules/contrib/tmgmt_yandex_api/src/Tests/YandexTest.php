<?php
/**
 * @file
 * Contains \Drupal\tmgmt_microsoft\Tests\MicrosoftTest.
 */

namespace Drupal\tmgmt_yandex_api\Tests;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\Tests\TMGMTTestBase;
use Drupal\Core\Url;

/**
 * Basic tests for the Microsoft translator.
 *
 * @group tmgmt_yandex_api
 */
class YandexTest extends TMGMTTestBase {

  /**
   * A tmgmt_translator with a server mock.
   *
   * @var Translator
   */
  protected $translator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_yandex_api', 'tmgmt_yandex_test');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->addLanguage('de');
    $this->translator = $this->createTranslator([
      'plugin' => 'microsoft',
      'settings' => [
        'url' => URL::fromUri('base://tmgmt_microsoft_mock/v2/Http.svc', array('absolute' => TRUE))->toString(),
      ],
    ]);
  }

  /**
   * Tests basic API methods of the plugin.
   */
  protected function testMicrosoft() {
    $job = $this->createJob();
    $job->translator = $this->translator->id();
    $job->save();
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $this->assertFalse($job->isTranslatable(), 'Check if the translator is not available at this point because we did not define the API parameters.');

    // Save a wrong client ID key.
    $this->translator->setSetting('client_id', 'wrong client_id');
    $this->translator->setSetting('client_secret', 'wrong client_secret');
    $this->translator->save();

    $translator = $job->getTranslator();
    $languages = $translator->getSupportedTargetLanguages('en');
    $this->assertTrue(empty($languages), t('We can not get the languages using wrong api parameters.'));

    // Save a correct client ID.
    $translator->setSetting('client_id', 'correct client_id');
    $translator->setSetting('client_secret', 'correct client_secret');
    $translator->save();

    // Make sure the translator returns the correct supported target languages.
    $translator->clearLanguageCache();
    $languages = $translator->getSupportedTargetLanguages('en');
    $this->assertTrue(isset($languages['de']));
    $this->assertTrue(isset($languages['es']));
    $this->assertTrue(isset($languages['it']));
    $this->assertTrue(isset($languages['zh-hans']));
    $this->assertTrue(isset($languages['zh-hant']));
    $this->assertFalse(isset($languages['zh-CHS']));
    $this->assertFalse(isset($languages['zh-CHT']));
    $this->assertFalse(isset($languages['en']));

    $this->assertTrue($job->canRequestTranslation()->getSuccess());

    $job->requestTranslation();

    // Now it should be needs review.
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isNeedsReview());
    }

    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    $this->assertEqual('Hallo Welt', $data['dummy']['deep_nesting']['#translation']['#text']);

    // Test continuous integration.
    $this->config('tmgmt.settings')
      ->set('submit_job_item_on_cron', TRUE)
      ->save();

    // Continuous settings configuration.
    $continuous_settings = [
      'content' => [
        'node' => [
          'enabled' => 1,
          'bundles' => [
            'test' => 1,
          ],
        ],
      ],
    ];

    $continuous_job = $this->createJob('en', 'de', 0, [
      'label' => 'Continuous job',
      'job_type' => Job::TYPE_CONTINUOUS,
      'translator' => $this->translator,
      'continuous_settings' => $continuous_settings,
    ]);
    $continuous_job->save();

    // Create an english node.
    $node = entity_create('node', array(
      'title' => $this->randomMachineName(),
      'uid' => 0,
      'type' => 'test',
      'langcode' => 'en',
    ));
    $node->save();

    $continuous_job->addItem('test_source', $node->getEntityTypeId(), $node->id());

    $continuous_job_items = $continuous_job->getItems();
    $continuous_job_item = reset($continuous_job_items);
    $this->assertTrue($continuous_job_item->getState() == JobItemInterface::STATE_INACTIVE);

    tmgmt_cron();

    $items = $continuous_job->getItems();
    $item = reset($items);
    $data = $item->getData();
    $this->assertEqual('Hallo Welt', $data['dummy']['deep_nesting']['#translation']['#text']);
    $this->assertTrue($continuous_job->getState() == Job::STATE_CONTINUOUS);
    $this->assertTrue($item->getState() == JobItemInterface::STATE_REVIEW);
  }

  /**
   * Tests the UI of the plugin.
   */
  protected function testMicrosoftUi() {
    // Translator edit form url.
    $url = Url::fromRoute('entity.tmgmt_translator.edit_form', ['tmgmt_translator' => $this->translator->id()]);
    $this->loginAsAdmin();

    // Try to connect with invalid credentials.
    $edit = [
      'settings[client_id]' => 'wrong client_id',
      'settings[client_secret]' => 'wrong client_secret',
    ];
    $this->drupalPostForm($url, $edit, t('Connect'));
    $this->assertText(t('The "Client ID", the "Client secret" or both are not correct.'));

    // Test connection with valid credentials.
    $edit = [
      'settings[client_id]' => 'correct client_id',
      'settings[client_secret]' => 'correct client_secret',
    ];
    $this->drupalPostForm($url, $edit, t('Connect'));
    $this->assertText('Successfully connected!');

    // Assert that default remote languages mappings were updated.
    $this->assertOptionSelected('edit-remote-languages-mappings-en', 'en');
    $this->assertOptionSelected('edit-remote-languages-mappings-de', 'de');

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText(t('@label configuration has been updated.', ['@label' => $this->translator->label()]));
  }

}
