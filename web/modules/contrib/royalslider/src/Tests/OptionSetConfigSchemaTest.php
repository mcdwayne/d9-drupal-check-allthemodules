<?php

/**
 * @file
 * Contains \Drupal\royalslider\Tests\OptionSetConfigSchemaTest.
 */

namespace Drupal\royalslider\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\royalslider\Entity\RoyalSliderOptionSetEntity;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the royalslider optionset config schema.
 *
 * @group royalslider
 */
class OptionSetConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'royalslider',
  );

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->typedConfig = \Drupal::service('config.typed');
  }

  /**
   * Tests the OptionSet config schema for royalslider.
   */
  public function testOptionSetConfigSchema() {
      $id = strtolower($this->randomMachineName());
      $optionset = RoyalSliderOptionSetEntity::create(array(
        'id' => $id,
      ));
      $optionset->save();

      $config = $this->config("royalslider.royalslider_optionset.$id");
      $this->assertEqual($config->get('id'), $id);
      $this->assertConfigSchema($this->typedConfig, $config->getName(), $config->get());
  }
}