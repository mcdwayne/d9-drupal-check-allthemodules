<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Tests migration of language content setting variables for nodes only.
 *
 * The variables are language_content_type_$type, i18n_node_options_* and
 * i18n_lock_node_*.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class LanguageContentSettingsNodeTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigrations([
      'd6_node_type',
      'd6_language_content_settings',
    ]);
  }

  /**
   * Tests migration of content language settings.
   */
  public function testLanguageContent() {
    // Assert that non translatable nodes are not translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'page');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'story');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    // Assert that products are not translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', 'default');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', 'product');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', 'product_kit');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', 'ship');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');
  }

}
