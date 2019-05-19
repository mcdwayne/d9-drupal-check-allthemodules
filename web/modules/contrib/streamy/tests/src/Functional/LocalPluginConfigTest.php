<?php

namespace Drupal\Tests\streamy\Functional;

use Drupal\streamy\StreamWrapper\FlySystemHelper;

/**
 * Tests the basic Streamy ensure() and the Local plugin functionality.
 *
 * @group streamy
 */
class LocalPluginConfigTest extends StreamyFunctionalTestBase {

  /**
   * Testing the ensure function that should return false
   * if a plugin has no correct settings.
   */
  public function testLocalPluginEnsureFail() {
    $scheme = 'streamy';
    $streamyFactory = \Drupal::service('streamy.factory');

    $fileSystem = $streamyFactory->getFilesystem($scheme);

    $this->assertTrue($fileSystem instanceof FlySystemHelper, 'Scheme "' . $scheme . '" is of the correct type');

    // Ensuring that it returns the right value
    $this->assertFalse($fileSystem->ensure(), 'Filesystem is failing ensure on scheme ' . $scheme);
  }

  public function testLocalPluginEnsureFailBecauseNotWritableFolder() {
    $scheme = 'streamy';

    // Setting correct plugin config

    // Plugin Local
    $pluginConfig = [
      'streamy' => [
        'master' => [
          'root' => '/',
        ],
        'slave'  => [
          'root' => '/root',
        ],
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    // Main streamy configuration
    $schemes = [
      'streamy' => [
        'master'      => 'local',
        'slave'       => 'local',
        'cdn_wrapper' => '',
        'enabled'     => 1,
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $schemes)
           ->save();

    // Ensuring that it returns the right value
    $streamyFactory = \Drupal::service('streamy.factory');
    $fileSystem = $streamyFactory->getFilesystem($scheme);

    $this->assertFalse($fileSystem->ensure(), 'Filesystem is failing ensure on scheme ' . $scheme);
  }

  /**
   * Testing the ensure function that should return true
   * with the plugins correctly set.
   */
  public function testLocalPluginEnsurePass() {
    $scheme = 'streamy';

    // Setting correct plugin config

    // Plugin Local
    $pluginConfig = [
      'streamy' => [
        'master' => [
          'root' => $this->getPublicFilesDirectory() . 'writablefolderlocal1',
        ],
        'slave'  => [
          'root' => $this->getPublicFilesDirectory() . 'writablefolderlocal2',
        ],
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    // Main streamy configuration
    $schemes = [
      'streamy' => [
        'master'      => 'local',
        'slave'       => 'local',
        'cdn_wrapper' => '',
        'enabled'     => 1,
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $schemes)
           ->save();

    // Ensuring that it returns the right value
    $streamyFactory = \Drupal::service('streamy.factory');
    $fileSystem = $streamyFactory->getFilesystem($scheme);

    $this->assertTrue($fileSystem->ensure(), 'Filesystem ensure is valid on scheme ' . $scheme);
  }

}
