<?php
/**
 * @file
 * Code for JSONTest.php.
 */

namespace Drupal\Tests\migrate_source_json\Unit;

use Drupal\migrate\Plugin\Migration;
use Drupal\migrate_source_json\Plugin\migrate\JSONReader;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Base unit test to build json file contents.
 *
 * @group migrate_source_json
 */
abstract class JSONUnitTestCase extends UnitTestCase {

 /**
  * Source configuration
  *
  * @var array
  */
  protected $configuration;

 /**
  * The plugin id.
  *
  * @var string
  */
  protected $pluginId;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * The mock migration plugin.
   *
   * @topo Swap it out for a mock instance after https://www.drupal.org/node/2694009
   *
   * @var \Drupal\migrate\Plugin\Migration
   */
  protected $plugin;

  /**
   * A Migrate Source object.
   */
  protected $source;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    $this->pluginId = 'test json migration';
    $this->pluginDefinition = array();
    $plugin = $this->prophesize(Migration::class);
    $plugin->getIdMap()
      ->willReturn(NULL);
    // @topo Swap it out for getHighWaterProperty after https://www.drupal.org/node/2694009
    $plugin->get(Argument::exact('highWaterProperty'))
      ->willReturn(NULL);

    $this->plugin = $plugin->reveal();

    $this->configuration = array(
      'path' => 'nested.json',
      'identifier' => 'id',
      'identifierDepth' => 1,
      'fields' => array('id', 'user_name', 'description'),
      'headers' => array(array('Accept' => 'application/json')),
      'clientClass' => JSONTestCaseClient::class,
      'readerClass' => JSONReader::class,
    );

  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object    Instantiated object that we will run method on.
   * @param string $methodName Method name to call
   * @param array  $parameters Array of parameters to pass into method.
   *
   * @return mixed Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = array()) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
  }

}

