<?php

namespace Drupal\sir_trevor\Tests\Unit\Plugin;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\sir_trevor\Plugin\SirTrevorBlock;
use Drupal\sir_trevor\Plugin\SirTrevorBlockPlugin;
use Drupal\sir_trevor\Plugin\SirTrevorMixin;
use Drupal\sir_trevor\Plugin\SirTrevorPlugin;
use Drupal\sir_trevor\Plugin\SirTrevorPluginManager;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\ImmutableConfigMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\ConfigFactorySpy;
use Drupal\Tests\sir_trevor\Unit\Plugin\TestDoubles\ModuleHandlerMock;
use Drupal\Tests\UnitTestCase;

/**
 * @group SirTrevor
 */
class SirTrevorPluginManagerTest extends UnitTestCase {
  /** @var \Drupal\Tests\sir_trevor\Unit\Plugin\TestDoubles\ModuleHandlerMock */
  protected $moduleHandler;
  /** @var ConfigFactorySpy */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    FileCacheFactory::setPrefix('prefix');
    $this->moduleHandler = new ModuleHandlerMock();
    $directories = [
      'module_a' => __DIR__ . '/fixtures/yml/module_a',
      'module_b' => __DIR__ . '/fixtures/yml/module_b',
      'module_c' => __DIR__ . '/fixtures/yml/module_c',
    ];

    $this->moduleHandler->setModuleDirectories($directories);
    $this->configFactory = new ConfigFactorySpy();
  }

  /**
   * @test
   */
  public function getDefinitions() {
    $expected = $this->getFixtureDefinitions();
    $this->assertEquals($expected, $this->getSut()->getDefinitions());
  }

  /**
   * @test
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage The "unknown" plugin does not exist.
   */
  public function getUnknownDefinition() {
    $this->getSut()->getDefinition('unknown');
  }

  /**
   * @test
   */
  public function getDefinition() {
    $this->assertEquals($this->getFixtureDefinition('generic'), $this->getSut()->getDefinition('generic'));
  }

  /**
   * @test
   */
  public function hasDefinition() {
    $this->assertTrue($this->getSut()->hasDefinition('generic'));
    $this->assertFalse($this->getSut()->hasDefinition('unknown'));
  }

  /**
   * @test
   */
  public function getInstance() {
    $definition = $this->getFixtureDefinition('generic');
    $this->assertEquals(new SirTrevorBlock($definition), $this->getSut()->getInstance($definition));
  }

  /**
   * @test
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   * @expectedExceptionMessage The "unknown" plugin does not exist.
   */
  public function createInstanceWithInvalidPlugin() {
    $this->getSut()->createInstance('unknown');
  }

  /**
   * @test
   */
  public function createInstanceWithValidPlugin() {
    $expected = new SirTrevorBlock($this->getFixtureDefinition('generic'));
    /** @var SirTrevorBlock $createdInstance */
    $createdInstance = $this->getSut()->createInstance('generic');
    $this->assertEquals($expected, $createdInstance);
    $this->assertEquals('some/path/to/display.js', $createdInstance->getDisplayJs());
    $this->assertEquals('some/path/to/display.css', $createdInstance->getDisplayCss());
    $this->assertEquals('some/path/to/editor.css', $createdInstance->getEditorCss());
    $this->assertEquals('generic', $createdInstance->getMachineName());
    $this->assertEquals('module_a', $createdInstance->getDefiningModule());
    $this->assertEquals('path/to/template.html.twig', $createdInstance->getTemplate());
  }

  /**
   * @test
   */
  public function pluginsCanDefineTheirOwnJavascript() {
    $expected = new SirTrevorBlock($this->getFixtureDefinition('editor_js'));

    /** @var SirTrevorBlock $createdInstance */
    $createdInstance = $this->getSut()->createInstance('editor_js');
    $this->assertEquals($expected, $createdInstance);
    $this->assertEquals('some/path/to/editor.js', $createdInstance->getEditorJs());
  }

  /**
   * @test
   */
  public function mixinsCanBeDefined() {
    $expected = new SirTrevorMixin($this->getFixtureDefinition('mixin_plugin'));

    /** @var SirTrevorMixin $createdInstance */
    $createdInstance = $this->getSut()->createInstance('mixin_plugin');
    $this->assertEquals($expected, $createdInstance);
    $this->assertEquals('some/path/to/editor.js', $createdInstance->getEditorJs());
  }

  /**
   * @test
   */
  public function getBlocksOnlyReturnsBlocks() {
    $expected = [
      $this->getSut()->getInstance($this->getSut()->getDefinition('generic')),
      $this->getSut()->getInstance($this->getSut()->getDefinition('editor_js')),
    ];

    $this->assertEquals($expected, $this->getSut()->getBlocks());
  }

  /**
   * @test
   */
  public function enabledBlocksReadsConfigFromConfigFactory() {
    $this->getSut()->getEnabledBlocks();

    $expected = [
      [
        'name' => 'get',
        'arguments' => ['sir_trevor.global'],
      ],
    ];

    $this->assertEquals($expected, $this->configFactory->getCalledMethods());
  }

  /**
   * @test
   */
  public function givenNoSpecificBlocksAreEnabled_allBlocksAreReturned() {
    $this->setEnabledBlocks([]);

    $this->assertEquals($this->getSut()->getBlocks(), $this->getSut()->getEnabledBlocks());
  }

  /**
   * @test
   */
  public function givenSingleBlockIsEnabled_onlyThatBlockInstanceIsReturned() {
    $this->setEnabledBlocks(['generic']);

    $expected = [
      $this->getSut()->getInstance($this->getSut()->getDefinition('generic')),
    ];
    $this->assertEquals($expected, $this->getSut()->getEnabledBlocks());
  }

  /**
   * @return \Drupal\sir_trevor\Plugin\SirTrevorPluginManager
   */
  private function getSut() {
    if (!$this->configFactory->has('sir_trevor.global')) {
      $this->setEnabledBlocks([]);
    }
    return new SirTrevorPluginManager($this->moduleHandler, $this->configFactory);
  }

  /**
   * @return array
   */
  protected function getFixtureDefinitions() {
    $expected['generic'] = [
      'template' => 'path/to/template.html.twig',
      'assets' => [
        'display' => [
          'js' => 'some/path/to/display.js',
          'css' => 'some/path/to/display.css'
        ],
        'editor' => [
          'css' => 'some/path/to/editor.css',
        ],
      ],
      'provider' => 'module_a',
      'id' => 'generic',
    ];
    $expected['editor_js'] = [
      'template' => 'path/to/template.html.twig',
      'assets' => [
        'editor' => [
          'js' => 'some/path/to/editor.js',
        ],
      ],
      'provider' => 'module_b',
      'id' => 'editor_js',
    ];
    $expected['mixin_plugin'] = [
      'mixin' => true,
      'assets' => [
        'editor' => [
          'js' => 'some/path/to/editor.js',
        ],
      ],
      'provider' => 'module_c',
      'id' => 'mixin_plugin',
    ];

    return $expected;
  }

  /**
   * @param string $key
   * @return array
   */
  private function getFixtureDefinition($key) {
    return $this->getFixtureDefinitions()[$key];
  }

  /**
   * @param $enabledBlocks
   */
  private function setEnabledBlocks($enabledBlocks) {
    $config = new ImmutableConfigMock();
    $config->initWithData(['enabled_blocks' => $enabledBlocks]);
    $this->configFactory->set('sir_trevor.global', $config);
  }
}
