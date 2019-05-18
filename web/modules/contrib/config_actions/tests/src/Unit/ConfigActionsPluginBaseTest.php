<?php

namespace Drupal\Tests\config_actions\Unit;

use Drupal\config_actions\ConfigActionsPluginBase;
use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * test the ConfigActionsPluginBase class
 *
 * @coversDefaultClass \Drupal\config_actions\ConfigActionsPluginBase
 * @group config_actions
 */
class ConfigActionsPluginBaseTest extends UnitTestCase {

  /**
   * @var \Drupal\config_actions\ConfigActionsServiceInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configActions;

  /**
   * Annotation data to be passed to plugin constructor.
   * @var array
   */
  protected $definition;

  public function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $this->configActions = $this->getMock(ConfigActionsServiceInterface::class);
    $container->set('config_actions', $this->configActions);
    \Drupal::setContainer($container);

    $this->definition = [
      'options' => [],
      'replace_in' => [],
      'data' => [],
    ];
  }

  /**
   * @covers ::setOptions
   * @covers ::getOption
   */
  public function testGetOption() {
    $options = [
      'source' => 'my source',
    ];
    $plugin = new ConfigActionsPluginBase($options, 'test', $this->definition, $this->configActions);
    $plugin->setOptions($options);
    $this->assertEquals('my source', $plugin->getOption('source'));
  }

  /**
   * @covers ::setOptions
   */
  public function testSetOptions() {
    $options = [
      'source' => 'node.type.@bundle@',
      'replace' => [
        '@bundle@' => 'article',
      ]
    ];
    $this->definition['replace_in'] = ['source'];
    $plugin = new ConfigActionsPluginBase($options, 'test', $this->definition, $this->configActions);
    $plugin->setOptions($options);
    $this->assertEquals('node.type.article', $plugin->getOption('source'));
  }

  /**
   * @covers ::parseOptions
   */
  public function testParseOptions() {
    $options = [
      'id' => 'testid',
      'source' => '@id@',
    ];
    $plugin = new ConfigActionsPluginBase($options, 'test', $this->definition, $this->configActions);
    $new_options = $plugin->parseOptions($options);
    $this->assertEquals('testid', $new_options['source']);
  }

  /**
   * @covers ::setOptions
   */
  public function testSimpleOptions() {
    $options = [
      'source' => 'node.type.@bundle@',
      '@bundle@' => 'article',
    ];
    $this->definition['replace_in'] = ['source'];
    $plugin = new ConfigActionsPluginBase($options, 'test', $this->definition, $this->configActions);
    $plugin->setOptions($options);
    $this->assertEquals('node.type.article', $plugin->getOption('source'));
  }

}
