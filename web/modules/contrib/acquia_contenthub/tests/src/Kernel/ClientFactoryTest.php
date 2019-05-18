<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Site\Settings;
use Acquia\ContentHubClient\Settings as AcquiaSettings;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class ClientFactoryTest.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ClientFactoryTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'field',
    'depcalc',
    'acquia_contenthub',
    'acquia_contenthub_test',
  ];

  /**
   * Test case when content hub configured via core config or UI.
   *
   * @param string $name
   *   Client name.
   * @param string $uuid
   *   Client UUID.
   * @param string $api_key
   *   API Key.
   * @param string $secret_key
   *   Secret Key.
   * @param string $url
   *   Hostname.
   * @param string $shared_secret
   *   Shared secret key.
   *
   * @dataProvider settingsDataProvider
   *
   * @see GetSettingsFromCoreConfig
   */
  public function testGetClientConfiguredByCoreConfig($name, $uuid, $api_key, $secret_key, $url, $shared_secret) {
    $admin_settings = \Drupal::configFactory()
      ->getEditable('acquia_contenthub.admin_settings');

    $admin_settings->set('client_name', $name);
    $admin_settings->set('origin', $uuid);
    $admin_settings->set('api_key', $api_key);
    $admin_settings->set('secret_key', $secret_key);
    $admin_settings->set('hostname', $url);
    $admin_settings->set('shared_secret', $shared_secret);
    $admin_settings->save();

    Cache::invalidateTags(['acquia_contenthub_settings']);

    /** @var \Drupal\acquia_contenthub\Client\ClientFactory $clientFactory */
    $clientFactory = \Drupal::service('acquia_contenthub.client.factory');

    $settings = $clientFactory->getClient()->getSettings();

    // Check that settings has loaded from correct storage (provider).
    $this->assertEquals($clientFactory->getProvider(), 'core_config');

    // Check all values.
    $this->assertEquals($settings->getName(), $name);
    $this->assertEquals($settings->getUuid(), $uuid);
    $this->assertEquals($settings->getApiKey(), $api_key);
    $this->assertEquals($settings->getSecretKey(), $secret_key);
    $this->assertEquals($settings->getUrl(), $url);
    $this->assertEquals($settings->getSharedSecret(), $shared_secret);
  }

  /**
   * Test case when content hub configured via system settings.
   *
   * @param string $name
   *   Client name.
   * @param string $uuid
   *   Client UUID.
   * @param string $api_key
   *   API Key.
   * @param string $secret_key
   *   Secret Key.
   * @param string $url
   *   Hostname.
   * @param string $shared_secret
   *   Shared secret key.
   *
   * @dataProvider settingsDataProvider
   */
  public function testGetClientConfiguredBySettings($name, $uuid, $api_key, $secret_key, $url, $shared_secret) {
    // Get existing values from settings.php file.
    $system_settings = Settings::getAll();
    // Merge our settings.
    $system_settings['acquia_contenthub.settings'] = new AcquiaSettings(
      $name,
      $uuid,
      $api_key,
      $secret_key,
      $url,
      $shared_secret
    );

    // Re-initialize (update) settings.
    new Settings($system_settings);

    /** @var \Drupal\acquia_contenthub\Client\ClientFactory $clientFactory */
    $clientFactory = \Drupal::service('acquia_contenthub.client.factory');
    $settings = $clientFactory->getClient()->getSettings();

    $this->assertEquals($clientFactory->getProvider(), 'core_settings');
    $this->assertEquals($settings->getName(), $name);
    $this->assertEquals($settings->getUuid(), $uuid);
    $this->assertEquals($settings->getApiKey(), $api_key);
    $this->assertEquals($settings->getSecretKey(), $secret_key);
    $this->assertEquals($settings->getUrl(), $url);
    $this->assertEquals($settings->getSharedSecret(), $shared_secret);
  }

  /**
   * Provides sample data for client's settings.
   *
   * @return array
   *   Settings.
   */
  public function settingsDataProvider() {
    return [
      [
        'test-client',
        '00000000-0000-0001-0000-123456789123',
        'kZvJl17RyLUhIOCdssssshm5j',
        'Sv6KgchlGWNgxBqFls123213MkmVwklnuOK2pIimlXss23123Xl',
        'https://dev-euc1.content-hub.acquia.dev',
        '12312321312321',
      ],
    ];
  }

}
