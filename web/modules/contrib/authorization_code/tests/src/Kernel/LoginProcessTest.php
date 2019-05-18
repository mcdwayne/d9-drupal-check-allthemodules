<?php

namespace Drupal\Tests\authorization_code\Kernel;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\Component\Utility\NestedArray;
use Drupal\KernelTests\KernelTestBase;

/**
 * Login process kernel test.
 *
 * @group authorization_code
 */
class LoginProcessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'authorization_code',
    'system',
    'user',
    'authorization_code_login_process_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->installEntitySchema('login_process');
  }

  /**
   * Test login process entity save and load.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testLoginProcessSaveAndLoad() {
    LoginProcess::create($this->loginProcessConfig())->save();

    $login_process = LoginProcess::load('test_login_process');
    $this->assertNotNull($login_process, 'Login process entity, loaded successfully.');
    $this->assertEquals($this->loginProcessConfig('user_identifier'), $login_process->get('user_identifier'));
    $this->assertEquals($this->loginProcessConfig('code_generator'), $login_process->get('code_generator'));
    $this->assertEquals($this->loginProcessConfig('code_sender'), $login_process->get('code_sender'));
  }

  /**
   * A login process configuration array.
   *
   * @param string[]|string $parents
   *   A path to the internal configuration property inside.
   *
   * @return mixed
   *   The complete login process config or a subset of it.
   */
  private function loginProcessConfig($parents = []) {
    $parents = is_array($parents) ? $parents : [$parents];
    $config = [
      'id' => 'test_login_process',
      'user_identifier' => ['plugin_id' => 'user_id', 'settings' => []],
      'code_generator' => ['plugin_id' => 'simple_rng', 'settings' => ['code_length' => 4]],
      'code_sender' => ['plugin_id' => 'ignore', 'settings' => ['message_template' => '']],
    ];
    return NestedArray::getValue($config, $parents);
  }

}
