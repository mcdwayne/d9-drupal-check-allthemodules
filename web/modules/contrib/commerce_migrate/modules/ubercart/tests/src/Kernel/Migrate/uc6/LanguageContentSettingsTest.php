<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Tests migration of language content setting variables for node and products.
 *
 * The variables are language_content_type_$type, i18n_node_options_* and
 * i18n_lock_node_*.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class LanguageContentSettingsTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'content_translation',
    'language',
    'menu_ui',
    'migrate_plus',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['node']);
    $this->installConfig(['commerce_product']);
    $this->executeMigrations([
      'd6_node_type',
      'uc6_product_type',
      'uc6_language_content_settings',
    ]);
  }

  /**
   * Tests migration of content language settings.
   */
  public function testLanguageContent() {
    // Assert that translatable products are still translatable.
    $config = $this->config('language.content_settings.commerce_product.product');
    $this->assertSame($config->get('target_entity_type_id'), 'commerce_product');
    $this->assertSame($config->get('target_bundle'), 'product');
    $this->assertSame($config->get('default_langcode'), 'current_interface');
    $this->assertTrue($config->get('third_party_settings.content_translation.enabled'));

    $config = $this->config('language.content_settings.commerce_product.product_kit');
    $this->assertSame($config->get('target_entity_type_id'), 'commerce_product');
    $this->assertSame($config->get('target_bundle'), 'product_kit');
    $this->assertSame($config->get('default_langcode'), 'current_interface');
    $this->assertTrue($config->get('third_party_settings.content_translation.enabled'));

    // Assert that non translatable nodes and products are not translatable.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'page');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'story');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', 'ship');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');

    $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', 'default');
    $this->assertTrue($config->isDefaultConfiguration());
    $this->assertFalse($config->isLanguageAlterable());
    $this->assertSame($config->getDefaultLangcode(), 'site_default');
  }

}
