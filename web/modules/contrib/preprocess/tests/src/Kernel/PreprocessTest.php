<?php

namespace Drupal\Tests\preprocess\Kernel;

use Drupal\preprocess\PreprocessInterface;
use Drupal\Core\Theme\MissingThemeDependencyException;
use Drupal\KernelTests\KernelTestBase;
use Exception;

/**
 * Tests Preprocess functionality.
 *
 * @group Preprocess
 */
class PreprocessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'preprocess',
    'preprocess_test',
  ];

  /**
   * The preprocess plugin manager.
   *
   * @var \Drupal\preprocess\PreprocessPluginManagerInterface
   */
  private $preprocessPluginManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Theme initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->preprocessPluginManager = $this->container->get('preprocess.plugin.manager');
    $this->themeManager = $this->container->get('theme.manager');
    $this->themeInitializer = $this->container->get('theme.initialization');
    $this->container->get('theme_installer')->install(['test_preprocess_theme']);
    $this->installConfig(['system']);
    $this->config('system.theme')->set('default', 'test_preprocess_theme')->save();
  }

  /**
   * Test the discovery of plugins in themes and modules.
   *
   * @param string $plugin_id
   *   The id of the plugin that should be discovered.
   * @param string $theme_name
   *   The name of the theme to use as the active theme.
   * @param bool $expected
   *   Whether or not the plugin is expected to be discovered.
   *
   * @dataProvider discoveryData
   */
  public function testDiscovery(string $plugin_id, string $theme_name, bool $expected): void {
    try {
      $active_theme = $this->themeInitializer->getActiveThemeByName($theme_name);
    }
    catch (MissingThemeDependencyException $exception) {
      $this->fail($exception->getMessage());
      return;
    }

    $this->themeManager->setActiveTheme($active_theme);
    self::assertSame($expected, \array_key_exists($plugin_id, $this->preprocessPluginManager->getDefinitions()));
  }

  /**
   * Test that only the processors for a hook are retrieved if they define it.
   *
   * @param string $hook
   *   The hook to get preprocessors for.
   * @param array $expected_plugin_ids
   *   The expected preprocessor plugin ids.
   *
   * @dataProvider getPreprocessorsData
   */
  public function testGetPreprocessors(string $hook, array $expected_plugin_ids): void {
    $preprocessors = $this->preprocessPluginManager->getPreprocessors($hook);
    $plugin_ids = \array_map(function (PreprocessInterface $preprocessor) {
      return $preprocessor->getPluginId();
    }, $preprocessors);

    $diff = \array_merge(\array_diff($plugin_ids, $expected_plugin_ids), \array_diff($expected_plugin_ids, $plugin_ids));
    self::assertEmpty($diff);
  }

  /**
   * Test preprocessing.
   *
   * @param array $element
   *   The element to render.
   * @param string $expected_class
   *   The class we expect the preprocessor to add to the element.
   *
   * @dataProvider preprocessData
   */
  public function testPreprocess(array $element, string $expected_class): void {
    try {
      $this->render($element);
      $this->assertRaw($expected_class, $this->getRawContent());
    }
    catch (Exception $exception) {
      $this->fail($exception->getMessage());
      return;
    }
  }

  /**
   * Data provider for testDiscovery().
   */
  public function discoveryData(): array {
    return [
      'preprocessor_theme_test_theme' => [
        'test_preprocess_theme.preprocessor',
        'test_preprocess_theme',
        TRUE,
      ],
      'preprocessor_theme_core_theme' => [
        'test_preprocess_theme.preprocessor',
        'core',
        FALSE,
      ],
      'preprocessor_module_test_theme' => [
        'preprocess_test.preprocessor',
        'test_preprocess_theme',
        TRUE,
      ],
      'preprocessor_module_core_theme' => [
        'preprocess_test.preprocessor',
        'core',
        TRUE,
      ],
    ];
  }

  /**
   * Data provider for testGetPreprocessors().
   */
  public function getPreprocessorsData(): array {
    return [
      'hook_preprocess_input' => [
        'input',
        ['preprocess_test.preprocessor'],
      ],
      'hook_preprocess_image' => [
        'image',
        ['test_preprocess_theme.preprocessor'],
      ],
      'hook_preprocess_fake_hook' => [
        'fake_hook',
        [],
      ],
    ];
  }

  /**
   * Data provider for testPreprocess().
   */
  public function preprocessData(): array {
    return [
      'preprocess_input' => [
        ['#type' => 'button', '#value' => $this->randomMachineName()],
        'my-test-input-class',
      ],
      'preprocess_image' => [
        ['#theme' => 'image', '#uri' => 'logo.svg'],
        'my-test-image-class',
      ],
    ];
  }

}
