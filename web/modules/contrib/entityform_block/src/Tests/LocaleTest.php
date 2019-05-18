<?php

namespace Drupal\entityform_block\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\Gettext;

/**
 * Tests localization aspects of the module.
 *
 * @group entityform_block
 */
class LocaleTest extends EntityFormBlockTest {

  public static $modules = [
    'language',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $file = new \stdClass();
    $file->uri =  \Drupal::service('file_system')->realpath(drupal_get_path('module', 'entityform_block') . '/tests/test.de.po');
    $file->langcode = 'de';
    Gettext::fileToDatabase($file, array());

    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->config('system.site')->set('default_langcode', 'de')->save();
  }

}
