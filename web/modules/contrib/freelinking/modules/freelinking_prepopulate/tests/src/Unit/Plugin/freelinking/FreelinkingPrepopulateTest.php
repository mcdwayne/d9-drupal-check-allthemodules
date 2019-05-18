<?php

namespace Drupal\Tests\freelinking_prepopulate\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\freelinking_prepopulate\Plugin\freelinking\FreelinkingPrepopulate;
use Drupal\Tests\UnitTestCase;

/**
 * Tests FreelinkingPrepopulate freelinking plugin.
 *
 * @group freelinking_prepopulate
 */
class FreelinkingPrepopulateTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mocks returning content types.
    $nodeTypeProphet = $this->prophesize('\Drupal\Core\Entity\EntityInterface');
    $nodeTypeProphet->id()->willReturn('page');
    $nodeTypeStorageProphet = $this->prophesize('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $nodeTypeStorageProphet
      ->loadMultiple()
      ->willReturn(['page' => $nodeTypeProphet->reveal()]);

    // Mocks the entity type manager.
    $entityTypeManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityTypeManagerProphet
      ->getStorage('node_type')
      ->willReturn($nodeTypeStorageProphet->reveal());
    $entityTypeManager = $entityTypeManagerProphet->reveal();

    // Mocks the entity field manager.
    $entityFieldManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityFieldManagerInterface');
    $entityFieldManagerProphet
      ->getFieldDefinitions('node', 'page');

    // Mocks the module handler interface.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('entity_field.manager', $entityFieldManagerProphet->reveal());
    $container->set('module_handler', $moduleHandlerProphet->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Asserts that the configuration is returned based on initial state.
   *
   * @param array $configuration
   *   The initial plugin configuration.
   * @param array $expected
   *   The expected return value.
   *
   * @dataProvider configurationProvider
   */
  public function testGetConfiguration(array $configuration, array $expected) {
    $definition = [
      'id' => 'freelinking_prepopulate',
      'title' => 'Prepopulate',
      'settings' => [
        'default_node_type' => 'page',
        'advanced' => ['title' => '0'],
        'failover' => 'search',
      ],
    ];

    $plugin = FreelinkingPrepopulate::create(
      \Drupal::getContainer(),
      $configuration,
      'freelinking_prepopulate',
      $definition);

    $this->assertArrayEquals($expected, $plugin->getConfiguration());
  }

  /**
   * Gets test arguments for testGetConfiguration.
   *
   * @return array
   *   An array of test arguments.
   */
  public function configurationProvider() {
    return [
      'empty configuration provided' => [
        [],
        [
          'settings' => [
            'default_node_type' => 'page',
            'advanced' => ['title' => FALSE],
            'failover' => 'search',
          ],
        ],
      ],
      'empty settings provided' => [
        [
          'settings' => [],
        ],
        [
          'settings' => [
            'default_node_type' => 'page',
            'advanced' => ['title' => FALSE],
            'failover' => 'search',
          ],
        ],
      ],
      'failover setting provided' => [
        [
          'settings' => ['failover' => 'error'],
        ],
        [
          'settings' => [
            'default_node_type' => 'page',
            'advanced' => ['title' => FALSE],
            'failover' => 'error',
          ],
        ],
      ],
    ];
  }

}
