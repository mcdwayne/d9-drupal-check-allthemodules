<?php

namespace Drupal\Tests\config_replace\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\config_replace\ConfigReplacer
 * @group config_replace
 */
class ConfigReplacerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'config_replace', 'config_replace_test', 'config_replace_test_rewrite', 'language'];

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\CachedStorage
   */
  protected $activeConfigStorage;

  /**
   * The configuration rewriter.
   *
   * @var \Drupal\config_replace\ConfigReplacerInterface
   */
  protected $configRewriter;

  /**
   * The language config factory override service.
   *
   * @var \Drupal\language\Config\LanguageConfigFactoryOverrideInterface
   */
  protected $languageConfigFactoryOverride;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configRewriter = $this->container->get('config_replace.config_replacer');
    $this->activeConfigStorage = $this->container->get('config.storage');
    $this->languageConfigFactoryOverride = $this->container->get('language.config_factory_override');
    $this->installSchema('system', ['sequence']);
    $this->installEntitySchema('user_role');
    $this->installConfig(['language', 'config_replace_test']);
  }

  /**
   * @covers ::rewriteModuleConfig
   * @covers ::rewriteConfig
   */
  public function testConfigRewrite() {
    $expected_original_data = [
      'label' => 'Test 1',
      'is_admin' => FALSE,
      'permissions' => [
        'access user profiles',
      ],
    ];

    // Verify that the original configuration data exists.
    $data = $this->activeConfigStorage->read('user.role.test1');
    $this->assertIdentical($data['label'], $expected_original_data['label']);
    $this->assertIdentical($data['permissions'], $expected_original_data['permissions']);

    // Rewrite configuration.
    $this->configRewriter->rewriteModuleConfig('config_replace_test_rewrite');

    // Test a rewrite where config_replace is not set.
    // Test that data is modified.
    $expected_rewritten_data = [
      'label' => 'Test 1 rewritten',
      // Unchanged.
      'is_admin' => FALSE,
      // Merged.
      'permissions' => [
        'access user profiles',
        'change own username',
      ],
    ];
    $user_role = $this->activeConfigStorage->read('user.role.test1');
    $this->assertEquals($user_role['label'], $expected_rewritten_data['label']);
    $this->assertEquals($user_role['is_admin'], $expected_rewritten_data['is_admin']);
    $this->assertEquals($user_role['permissions'], $expected_rewritten_data['permissions']);

    // Test a rewrite where config_replace is set to an unsupported value.
    // Test that data is modified.
    $expected_rewritten_data = [
      'label' => 'Test 2 rewritten',
      // Unchanged.
      'is_admin' => FALSE,
      // Merged.
      'permissions' => [
        'access user profiles',
        'change own username',
      ],
    ];
    $user_role = $this->activeConfigStorage->read('user.role.test2');
    $this->assertEquals($user_role['label'], $expected_rewritten_data['label']);
    $this->assertEquals($user_role['is_admin'], $expected_rewritten_data['is_admin']);
    $this->assertEquals($user_role['permissions'], $expected_rewritten_data['permissions']);
    // Test that the "config_replace" key was unset.
    $this->assertFalse(isset($user_role['config_replace']));

    // Test a rewrite where config_replace is set to "replace".
    // Test that data is replaced.
    $expected_rewritten_data = [
      'label' => 'Test 3 replaced',
      // Unchanged.
      'is_admin' => FALSE,
      // Replaced.
      'permissions' => [
        'change own username',
      ],
    ];
    $user_role = $this->activeConfigStorage->read('user.role.test3');
    $this->assertEquals($user_role['label'], $expected_rewritten_data['label']);
    $this->assertEquals($user_role['is_admin'], $expected_rewritten_data['is_admin']);
    $this->assertEquals($user_role['permissions'], $expected_rewritten_data['permissions']);
    // Test that the "config_replace" key was unset.
    $this->assertFalse(isset($user_role['config_replace']));

    // Test a multilingual rewrite.
    $expected_rewritten_data = [
      'label' => 'Test 4 réécrit',
    ];
    $user_role = $this->languageConfigFactoryOverride->getOverride('fr', 'user.role.test4')->get();
    $this->assertEquals($user_role['label'], $expected_rewritten_data['label']);
  }

}
