<?php

namespace Drupal\Tests\user_account_language_negotiation\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests our hooks.
 *
 * @group user_account_language_negotiation
 */
class HookTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'locale',
    'user_account_language_negotiation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('locale', [
      'locales_source',
      'locales_target',
      'locales_location',
    ]);
  }

  /**
   * Test our hook_install implementation.
   */
  public function testIfInstallHookImportsTranslationsOfLanguageNames() {
    ConfigurableLanguage::create(['id' => 'fi'])->save();

    require_once __DIR__ . '/../../../user_account_language_negotiation.install';
    user_account_language_negotiation_install();

    self::assertEquals('Suomi', t('Finnish', [], ['langcode' => 'fi']));
  }

}
