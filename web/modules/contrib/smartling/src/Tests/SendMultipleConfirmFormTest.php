<?php

/**
 * @file
 * Contains \Drupal\smartling\Tests\SendMultipleConfirmFormTest.
 */

namespace Drupal\smartling\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Class SendMultipleConfirmFormTest
 * @package Drupal\smartling\Tests
 * @group smartling
 */
class SendMultipleConfirmFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['smartling', 'action', 'views', 'node', 'field', 'dblog', 'block', 'content_translation'];

  protected $profile = 'standard';

  /**
   * The added languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser([
      'administer smartling',
      'use smartling entity translation',
      'access administration pages',
      'access content',
      'access content overview',
      'create content translations',
    ]);
    for ($i = 0; $i < 5; $i++) {
      $node = $this->drupalCreateNode([
        'title' => $this->randomString(),
        'body' => [
          'value' => $this->randomString(),
        ],
        'type' => 'page',
      ]);

      $node->save();
    }
  }

  public function testActionForm() {
    $this->setupLanguages();
    $this->enableTranslation();
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/content');
    $value = $this->xpath('//input[1][@name="node_bulk_form[0]"]')[0]->value;
    $this->drupalPostForm('smartling/send', [
      'action' => 'translate_node_to_all_configured_languages',
      'node_bulk_form[0]' => $value,
    ], 'Apply');
    $this->assertText('Are you sure you want to send these content to Smartling?');
    $this->assertText('Entities to send');
    $this->assertText('nl');
    $this->assertText('es');
    $this->drupalPostForm(NULL, [
      'locales[nl]' => 'nl',
      'locales[es]' => 'es',
      'confirm' => 1,

    ], 'Send to Smartling');
  }

  /**
   * Adds additional languages.
   *
   * @param array $langcodes
   *   Array of ISO language codes.
   */
  protected function setupLanguages($langcodes = ['es', 'nl']) {
    $this->langcodes = $langcodes;
    $smartling_languages = [];
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
      $smartling_languages[$langcode] = $langcode . '-' . strtoupper($langcode);
    }
    array_unshift($this->langcodes, \Drupal::languageManager()->getDefaultLanguage()->getId());

    $this->config('smartling.settings')
      ->set('account_info.enabled_languages', array_combine($langcodes, $langcodes))
      ->set('account_info.language_mappings', array_combine($langcodes, $smartling_languages))
      ->save();
  }

  /**
   * Enables translation for the current entity type and bundle.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
    drupal_static_reset();
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

}
