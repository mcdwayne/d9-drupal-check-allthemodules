<?php

namespace Drupal\Tests\token_custom\Kernel;

use Drupal\token_custom\Entity\TokenCustomType;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the token_custom config schema.
 *
 * @group token_custom
 */
class TokenCustomConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'token_custom',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->typedConfig = \Drupal::service('config.typed');
  }

  /**
   * Tests the token_custom config schema for TokenCustomType plugins.
   */
  public function testTokenCustomConfigSchema() {
    $id = 'my_token_type';
    $token_custom_type = TokenCustomType::create([
      'uuid' => '6a05119f-f3a0-43b6-bc03-070a67cb4529',
      'status' => 1,
      'dependencies' => [],
      'machineName' => $id,
      'name' => 'My Token Type',
      'description' => 'This is a custom token type for testing purposes.',
    ]);
    $token_custom_type->save();
    $config = $this->config("token_custom.type.$id");
    $this->assertEqual($config->get('machineName'), $id);
    $this->assertConfigSchema($this->typedConfig, $config->getName(), $config->get());
  }

}
