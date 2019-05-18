<?php

namespace Drupal\Tests\config_replace\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\config_replace\ConfigReplacer
 * @group config_replace
 */
class NonexistentInitialConfigTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'config_replace', 'config_replace_nonexisting', 'language'];

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
  }

  /**
   * @covers ::rewriteModuleConfig
   * @covers ::rewriteConfig
   * @expectedException \Drupal\config_replace\Exception\NonexistentInitialConfigException
   */
  function testConfigRewrite() {
    $this->configRewriter->rewriteModuleConfig('config_replace_nonexisting');
  }

}
