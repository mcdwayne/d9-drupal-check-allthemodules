<?php

namespace Drupal\Tests\warden\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\warden\Service\WardenManager;
use Drupal\Core\Extension\Extension;

/**
 * @coversDefaultClass \Drupal\warden\Service\WardenManager
 * @group warden
 */
class WardenManagerTest extends UnitTestCase {

  /**
   * @var WardenManager
   */
  protected $wardenManager;

  /**
   * @var string
   */
  protected $token;

  /**
   * @var \Drupal\Core\Extension\InfoParser|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $infoParser;

  /**
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->token = hash('sha256', 42);

    $this->infoParser = $this->getMock('Drupal\Core\Extension\InfoParser', array(), array(), '', FALSE);
    $this->configFactory = $this->getMock('Drupal\Core\Config\ConfigFactory', array(), array(), '', FALSE);

    $this->expectConfigFactoryAccess();

    $this->wardenManager = new WardenManager($this->configFactory);
    $this->wardenManager
      ->setBaseUrl('http://www.example.com')
      ->setTime(12345678)
      ->setInfoParser($this->infoParser)
      ->setThemes([])
      ->setModules([]);
  }

  /**
   * Expect access to the config factory via the WardenManager constructor.
   */
  protected function expectConfigFactoryAccess() {
    $wardenConfig = $this->getMock('Drupal\Core\Config\Config', array(), array(), '', FALSE);
    $systemConfig = $this->getMock('Drupal\Core\Config\Config', array(), array(), '', FALSE);

    $this->configFactory->expects($this->any())
      ->method('get')
      ->will($this->returnCallback(
        function ($setting_name) use ($wardenConfig, $systemConfig) {
          $settings = [
            'warden.settings' => $wardenConfig,
            'system.site' => $systemConfig,
          ];
          return $settings[$setting_name];
        })
      );

    $systemConfig->expects($this->once())
      ->method('get')
      ->with('name')
      ->willReturn('My Website');

    $wardenConfig->expects($this->any())
      ->method('get')
      ->will($this->returnCallback(
        function ($setting_name) {
          $settings = [
            'warden_preg_match_contrib' => '{^modules\/contrib\/*}',
            'warden_preg_match_custom' => '{^modules\/custom\/*}',
            'warden_match_custom' => TRUE,
            'warden_match_contrib' => TRUE,
            'warden_token' => $this->token,
            'warden_server_host_path' => 'http://warden.example.com',
            'warden_http_username' => '',
            'warden_http_password' => '',
            'warden_certificate_path' => '',
          ];
          return $settings[$setting_name];
        })
      );
  }

  /**
   * @param string $name
   * @param string $type
   * @return Extension|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getMockExtension($name, $type = 'contrib') {
    /** @var Extension|\PHPUnit_Framework_MockObject_MockObject $module_b */
    $extension = $this->getMock('Drupal\Core\Extension\Extension', array(), array(), '', FALSE);
    $extension->expects($this->once())
      ->method('getPathname')
      ->willReturn("modules/{$type}/{$name}/{$name}.info.yml");

    return $extension;
  }

  /**
   * @param array $moduleNames
   *   A list of module names (e.g. ['module_a', 'module_b']
   *   The info parser will expect there to be 2 calls to parse
   *   and it will return an array each time of the form
   *   ['version' => '8.x-1.0']
   */
  protected function expectContribModules(array $moduleNames) {
    $results = [];

    foreach ($moduleNames as $moduleName) {
      $results["modules/contrib/{$moduleName}/{$moduleName}.info.yml"] = [
        'version' => '8.x-1.0',
      ];
    }

    $this->infoParser->expects($this->exactly(count($moduleNames)))
      ->method('parse')
      ->will($this->returnCallback(
        function ($info_filename) use ($results) {
          return $results[$info_filename];
        })
      );
  }

  /**
   * Tests generating module data with no modules.
   */
  public function testGenerateDataNoModules() {
    $expected_data = [
      'core' => [
        'drupal' => [
          'version' => \Drupal::VERSION,
        ],
      ],
      'contrib' => [],
      'custom' => [],
      'url' => 'http://www.example.com',
      'site_name' => 'My Website',
      'key' => $this->token,
      'time' => 12345678,
    ];

    $actual_data = $this->wardenManager->generateSiteData();
    $this->assertEquals($expected_data, $actual_data);
  }


  /**
   * Tests generating module data with one module.
   */
  public function testGenerateDataOneModule() {
    $expected_data = [
      'core' => [
        'drupal' => [
          'version' => \Drupal::VERSION,
        ],
      ],
      'contrib' => [
        'Module A' => [
          'version' => '8.x-1.0',
        ],
      ],
      'custom' => [],
      'url' => 'http://www.example.com',
      'site_name' => 'My Website',
      'key' => $this->token,
      'time' => 12345678,
    ];

    $this->wardenManager->setModules([
        'module_a' => $this->getMockExtension('module_a'),
      ]
    );

    $this->infoParser->expects($this->once())
      ->method('parse')
      ->with('modules/contrib/module_a/module_a.info.yml')
      ->willReturn([
        'project' => 'Module A',
        'version' => '8.x-1.0',
      ]);

    $actual_data = $this->wardenManager->generateSiteData();
    $this->assertEquals($expected_data, $actual_data);
  }

  /**
   * Tests generating module data with two modules.
   */
  public function testGenerateDataTwoModules() {
    $expected_data = [
      'core' => [
        'drupal' => [
          'version' => \Drupal::VERSION,
        ],
      ],
      'contrib' => [
        'module_a' => [
          'version' => '8.x-1.0',
        ],
        'module_b' => [
          'version' => '8.x-1.0',
        ],
      ],
      'custom' => [],
      'url' => 'http://www.example.com',
      'site_name' => 'My Website',
      'key' => $this->token,
      'time' => 12345678,
    ];

    $this->wardenManager->setModules([
        'module_a' => $this->getMockExtension('module_a'),
        'module_b' => $this->getMockExtension('module_b'),
      ]
    );

    $this->expectContribModules(['module_a', 'module_b']);

    $actual_data = $this->wardenManager->generateSiteData();
    $this->assertEquals($expected_data, $actual_data);
  }

}
