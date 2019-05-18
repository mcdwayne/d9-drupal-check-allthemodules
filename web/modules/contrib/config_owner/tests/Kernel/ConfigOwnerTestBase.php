<?php

declare(strict_types = 1);

namespace Drupal\Tests\config_owner\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Base class for config owner Kernel tests.
 */
class ConfigOwnerTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'config_filter',
    'config_owner',
    'config_owner_test',
    'config_translation',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
    $this->installConfig(['config_owner_test']);

    // Create the French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Makes default config changes used in tests.
   *
   * It makes changes to:
   *
   * - entire owned config objects
   * - partially owned config objects
   * - non-owned objects.
   */
  protected function performDefaultConfigChanges() {
    $this->config('config_owner_test.settings')
    // Owned keys.
      ->set('main_color', 'yellow')
      ->set('other_colors.primary', 'brown')
      ->set('other_colors.settings.allowed', FALSE)
    // Not owned key.
      ->set('allowed_colors', ['blue', 'orange'])
      ->set('other_colors.secondary', 'black')
      ->save();

    $this->config('config_owner_test.tps')
      // Third party settings which we should not own by default.
      ->set('third_party_settings.distribution_module.colorize', FALSE)
      ->set('content.field_three.third_party_settings.distribution_module.color', 'green')
      ->save();

    $this->config('config_owner_test.tps_ignore')
      // Third party settings which we should own because we specified them.
      ->set('third_party_settings.distribution_module.color', 'green')
      ->set('content.field_one.third_party_settings.distribution_module.colorize', TRUE)
      ->set('content.field_two.third_party_settings.distribution_module.colorize', TRUE)
      // This we don't own so the change should take effect.
      ->set('content.field_two.third_party_settings.distribution_module.color', 'black')
      ->save();

    $this->config('config_owner_test.test_config.one')
      ->set('name', 'The new name')
      // The entire config is owned.
      ->save();

    $this->config('system.mail')
      ->set('interface', ['default' => 'dummy'])
      // The entire config is owned via the "owned" folder.
      ->save();

    $this->config('system.site')
      ->set('name', 'The new site name')
      // The entire config is not owned.
      ->save();

    // Translate a configuration that is owned.
    /** @var \Drupal\Core\Config\Config $config */
    $this->container->get('language_manager')
      ->getLanguageConfigOverride('fr', 'system.maintenance')
      ->set('message', 'The French maintenance message')
      ->save();
  }

}
