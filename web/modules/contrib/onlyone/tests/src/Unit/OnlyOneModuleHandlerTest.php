<?php

namespace Drupal\Tests\onlyone\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\onlyone\OnlyOneModuleHandler;
use Drupal\Core\Url;

/**
 * Tests the OnlyOneModuleHandler class methods.
 *
 * @group onlyone
 * @coversDefaultClass \Drupal\onlyone\OnlyOneModuleHandler
 */
class OnlyOneModuleHandlerTest extends UnitTestCase {

  /**
   * A module handler instance.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * A renderer instance.
   *
   * @var Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * A module extension list instance.
   *
   * @var Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The OnlyOneModuleHandler Object.
   *
   * @var Drupal\onlyone\OnlyOneModuleHandler
   */
  protected $onlyOneModuleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if (!defined('DRUPAL_MINIMUM_PHP')) {
      define('DRUPAL_MINIMUM_PHP', '5.5.9');
    }

    // Module Handler mock.
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    // Renderer mock.
    $this->renderer = $this->createMock('Drupal\Core\Render\RendererInterface');
    // ModuleExtensionList mock.
    $this->moduleExtensionList = $this->createMock('Drupal\Core\Extension\ModuleExtensionList');

    // Creating the object.
    $this->onlyOneModuleHandler = new OnlyOneModuleHandler($this->moduleHandler, $this->renderer, $this->moduleExtensionList);
  }

  /**
   * Tests the returned link from OnlyOneModuleHandler::getModuleHelpPageLink().
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $module_machine_name
   *   The module machine name.
   * @param string $module_name_alternate
   *   Alternate module name to use if the module is not present in the site.
   * @param string $emphasize
   *   Use this parameter to wrap with <em> tags the module name if the module
   *   is not installed or not present in the site.
   *
   * @covers ::getModuleHelpPageLink
   * @dataProvider providerGetModuleHelpPageLink
   */
  public function testGetModuleHelpPageLink($expected, $module_machine_name, $module_name_alternate, $emphasize = FALSE) {
    // All the installed modules.
    $modules = [];

    $modules['admin_toolbar']['name'] = 'Admin Toolbar';
    $modules['modules_weight']['name'] = 'Modules Weight';
    $modules['no_autocomplete']['name'] = 'No Autocomplete';
    $modules['node']['name'] = 'Node';
    $modules['views']['name'] = 'Views';
    $modules['workflows']['name'] = 'Workflows';

    // Mocking getAllInstalledInfo method.
    $this->moduleExtensionList->expects($this->any())
      ->method('getAllInstalledInfo')
      ->willReturn($modules);

    // Modules installed and implementing the hook_help.
    $modules_with_hook_help = [
      'admin_toolbar',
      'modules_weight',
      'no_autocomplete',
    ];

    // Mocking getImplementations method.
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->with('help')
      ->willReturn($modules_with_hook_help);

    // Here $module_name_alternate made the works as $module_name.
    $build = [
      '#type' => 'link',
      '#title' => $module_name_alternate,
      '#url' => Url::fromRoute('help.page', ['name' => $module_machine_name]),
      '#cache' => [
        'tags' => [
          'config:core.extension',
        ],
      ],
    ];

    // Mocking render method.
    $this->renderer->expects($this->any())
      ->method('render')
      ->with($build)
      ->willReturn($expected);

    // Testing the function.
    $this->assertEquals($expected, $this->onlyOneModuleHandler->getModuleHelpPageLink($module_machine_name, $module_name_alternate, $emphasize));
  }

  /**
   * Data provider for testGetModuleHelpPageLink().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from existsNodesContentType().
   *   - 'module_machine_name' - The module machine name.
   *   - 'module_name_alternate' - Alternate module name.
   *   - 'emphasize' - Boolean for wrap with <em> tags the module name.
   *
   * @see testExistsNodesContentType()
   */
  public function providerGetModuleHelpPageLink() {
    $tests = [
      // No existing modules.
      ['<em>Action</em>', 'action', 'Action', TRUE],
      ['Asana Module', 'asana', 'Asana Module', FALSE],
      ['Webform', 'webform', 'Webform'],
      // Existing installed modules.
      ['Admin Toolbar', 'admin_toolbar', 'Admin Toolbar', TRUE],
      ['Modules Weight', 'modules_weight', 'Modules Weight'],
      ['No Autocomplete', 'no_autocomplete', 'No Autocomplete', FALSE],
      // Existing not installed modules.
      ['<em>Node</em>', 'node', 'Node Module', TRUE],
      ['Views', 'views', 'Views Module', FALSE],
      ['Workflows', 'workflows', 'Workflows Module'],
    ];

    return $tests;
  }

}
