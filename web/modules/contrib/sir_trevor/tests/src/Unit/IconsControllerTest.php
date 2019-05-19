<?php

namespace Drupal\sir_trevor\Tests\Unit;

use Drupal\sir_trevor\Controller\IconsController;
use Drupal\Tests\sir_trevor\Unit\Plugin\TestDoubles\ModuleHandlerMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\ContainerSpy;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\IconSvgMergerMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorPluginManagerMock;
use Symfony\Component\HttpFoundation\Response;

define('DRUPAL_ROOT','ROOT');

/**
 * @package Drupal\sir_trevor\Tests
 * @group SirTrevor
 */
class IconsControllerTest extends \PHPUnit_Framework_TestCase {
  /** @var SirTrevorPluginManagerMock */
  private $blockPluginManager;
  /** @var IconSvgMergerMock */
  private $iconSvgMerger;
  /** @var ModuleHandlerMock */
  private $moduleHandler;
  /** @var IconsController */
  private $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->blockPluginManager = new SirTrevorPluginManagerMock();
    $this->iconSvgMerger = new IconSvgMergerMock();
    $this->moduleHandler = new ModuleHandlerMock();
    $this->sut = new IconsController($this->blockPluginManager, $this->iconSvgMerger, $this->moduleHandler);
  }

  /**
   * @test
   */
  public function givenNoDefinitions_getIconsReturnsLibraryIconsFile() {
    $expectedFiles = [DRUPAL_ROOT . '/libraries/sir-trevor/build/sir-trevor-icons.svg'];
    $this->assertResponseContentEquals($expectedFiles);

    return $expectedFiles;
  }

  /**
   * @test
   * @depends givenNoDefinitions_getIconsReturnsLibraryIconsFile
   */
  public function givenDefinitions_getIconsReturnsCombinedIconsFile(array $expectedFiles) {
    $definitions[] = [
      'provider' => 'module_a',
      'assets' => [
        'icon_file' => 'icons.svg'
      ],
    ];
    $definitions[] = [
      'provider' => 'module_b',
      'assets' => [
        'icon_file' => 'module_b_icons.svg'
      ],
    ];
    $definitions[] = [
      'provider' => 'module_b',
      'assets' => [
        'icon_file' => 'module_b_icons.svg'
      ],
    ];
    $this->blockPluginManager->setDefinitions($definitions);
    $this->moduleHandler->setModuleDirectories([
      'module_a' => 'mod_a',
      'module_b' => 'mod_b'
    ]);

    $expectedFiles[] = 'mod_a/icons.svg';
    $expectedFiles[] = 'mod_b/module_b_icons.svg';
    $this->assertResponseContentEquals($expectedFiles);
  }

  /**
   * @test
   */
  public function create() {
    $container = new ContainerSpy();

    try {
      IconsController::create($container);
    }
    catch (\TypeError $e) {
      // We are not passing actual arguments to the constructor and therefore
      // expect a TypeError to be thrown as NULL is never the correct type.
    }

    $container->assertNumberOfServicesRetrieved(3);
    $container->assertServiceRetrieved('plugin.manager.sir_trevor');
    $container->assertServiceRetrieved('sir_trevor.icon.svg.merger');
    $container->assertServiceRetrieved('module_handler');
  }

  /**
   * @param $fileNames
   */
  protected function assertResponseContentEquals($fileNames) {
    $expected = Response::create($this->iconSvgMerger->merge($fileNames), 200, ['Content-Type' => 'image/svg+xml']);
    $this->assertEquals($expected, $this->sut->getIcons());
  }
}
