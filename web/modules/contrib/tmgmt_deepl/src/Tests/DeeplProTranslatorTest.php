<?php

namespace Drupal\tmgmt_deepl\Tests;

use Drupal\tmgmt\Tests\TMGMTTestBase;
use Drupal\tmgmt_deepl\Plugin\tmgmt\Translator\DeeplProTranslator;
use Drupal\Core\Url;

/**
 * Basic tests for the DeepL Pro translator.
 *
 * @group tmgmt_deepl
 */
class DeeplProTranslatorTest extends TMGMTTestBase {

  /**
   * A tmgmt_translator with a server mock.
   *
   * @var \Drupal\tmgmt\TranslatorInterface
   */
  protected $translator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tmgmt_deepl', 'tmgmt_deepl_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->addLanguage('de');
    // Override plugin params to query tmgmt_deepl_test mock service instead
    // of DeepL Pro Translate service.
    $this->translator = $this->createTranslator([
      'plugin' => 'deepl_pro',
      'settings' => [
        'url' => Url::fromUri('base://tmgmt_deepl_test/translate', ['absolute' => TRUE])->toString(),
        'url_usage' => Url::fromUri('base://tmgmt_deepl_test/usage', ['absolute' => TRUE])->toString(),
        'auth_key' => 'correct key',
      ],
    ]);
  }

  /**
   * Tests basic API methods of the plugin.
   *
   * @todo Add test for continuous integration / fix breaking tests.
   */
  protected function testDeeplPro() {
    $plugin = $this->translator->getPlugin();
    $this->assertTrue($plugin instanceof DeeplProTranslator, 'Plugin is a DeeplProTranslator');

    $job = $this->createJob();
    $job->translator = $this->translator->id();
    $job->save();
    $item = $job->addItem('test_source', 'test', '1');
    $item->data = [
      'wrapper' => [
        '#text' => 'Hello world',
      ],
    ];
    $item->save();

    $this->assertFalse($job->isTranslatable(), 'Check if the translator is not available at this point because we did not define the API parameters.');

    // Save a wrong api key.
    $this->translator->setSetting('auth_key', 'wrong key');
    $this->translator->save();

    // Save a correct api key.
    $this->translator->setSetting('auth_key', 'correct key');
    $this->translator->save();

    // Make sure the translator returns the correct supported target languages.
    $this->translator->clearLanguageCache();
    $languages = $this->translator->getSupportedTargetLanguages('EN');

    $this->assertTrue(isset($languages['DE']));
    $this->assertTrue(isset($languages['FR']));
    // As we requested source language english it should not be included.
    $this->assertTrue(!isset($languages['EN']));

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
  }

  /**
   * Tests the UI of the plugin.
   */
  protected function testDeeplUi() {
    $url = Url::fromRoute('entity.tmgmt_translator.edit_form', ['tmgmt_translator' => $this->translator->id()]);
    $this->loginAsAdmin();
    $edit = [
      'settings[auth_key]' => 'wrong key',
    ];
    $this->drupalPostForm($url, $edit, t('Save'));
    $this->assertText(t('The "DeepL Pro authentication key" is not correct.'));
    $edit = [
      'settings[auth_key]' => 'correct key',
    ];
    $this->drupalPostForm($url, $edit, t('Save'));
    $this->assertText(t('@label configuration has been updated.', ['@label' => $this->translator->label()]));
  }

}
