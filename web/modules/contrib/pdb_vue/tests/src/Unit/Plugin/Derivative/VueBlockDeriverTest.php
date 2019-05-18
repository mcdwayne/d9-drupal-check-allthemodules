<?php

namespace Drupal\Tests\pdb_vue\Unit\Plugin\Derivative;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pdb_vue\Plugin\Derivative\VueBlockDeriver;
use Drupal\pdb\ComponentDiscoveryInterface;

/**
 * @coversDefaultClass \Drupal\pdb_vue\Plugin\Derivative\VueBlockDeriver
 * @group pdb_vue
 */
class VueBlockDeriverTest extends UnitTestCase {

  /**
   * Mocked Component Discovery.
   *
   * @var \Drupal\pdb\ComponentDiscoveryInterface
   */
  protected $componentDiscovery;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * Instance of the Block Deriver.
   *
   * @var \Drupal\pdb_vue\Plugin\Derivative\VueBlockDeriver
   */
  protected $deriver;

  /**
   * Create the setup for constants and configFactory stub.
   */
  protected function setUp() {
    parent::setUp();

    // Stub the Config Factory.
    $this->configFactory = $this->getConfigFactoryStub([
      'pdb_vue.settings' => [
        'development_mode' => 0,
      ],
    ]);

    // Mock the UUID service.
    $this->componentDiscovery = $this->prophesize(ComponentDiscoveryInterface::CLASS);
    $this->componentDiscovery->getComponents()->willReturn([
      'block_1' => (object) [
        'type' => 'pdb',
        'info' => [
          'name' => 'Block 1',
          'machine_name' => 'block_1',
          'presentation' => 'vue',
        ],
      ],
      'vue_example_1' => (object) [
        'type' => 'pdb',
        'info' => [
          'name' => 'Vue Example 1',
          'type' => 'vue-example-1',
          'presentation' => 'vue',
        ],
      ],
    ]);

    $this->deriver = new VueBlockDeriver(
      $this->componentDiscovery->reveal(),
      $this->configFactory
    );
  }

  /**
   * Tests the create method.
   *
   * @see ::create()
   */
  public function testCreate() {
    $base_plugin_id = 'pdb_vue';

    $container = $this->prophesize(ContainerInterface::CLASS);
    $container->get('pdb.component_discovery')
      ->willReturn($this->componentDiscovery);
    $container->get('config.factory')->willReturn($this->configFactory);

    $instance = VueBlockDeriver::create(
      $container->reveal(),
      $base_plugin_id
    );
    $this->assertInstanceOf('Drupal\pdb_vue\Plugin\Derivative\VueBlockDeriver', $instance);
  }

  /**
   * Tests the getDerivativeDefinitions() method.
   */
  public function testGetDerivativeDefinitions() {
    $base_plugin_definition = [
      'provider' => 'pdb_vue',
    ];

    // vue_example_1 should not appear due to debug mode being off.
    $expected = [
      'block_1' => [
        'info' => [
          'name' => 'Block 1',
          'machine_name' => 'block_1',
          'presentation' => 'vue',
        ],
        'provider' => 'pdb_vue',
        'admin_label' => 'Block 1',
        'cache' => ['max-age' => 0],
      ],
    ];

    $return = $this->deriver->getDerivativeDefinitions($base_plugin_definition);
    $this->assertArrayEquals($expected, $return);
  }

}
