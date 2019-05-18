<?php

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\Traits\ExpectDeprecationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that core image manipulations work properly through Imagemagick.
 *
 * @group Imagemagick
 */
class ImagemagickDeprecationTest extends BrowserTestBase {

  use ExpectDeprecationTrait;
  use TestFileCreationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A directory for image test file results.
   *
   * @var string
   */
  protected $testDirectory;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'simpletest',
    'file_test',
    'imagemagick',
    'imagemagick_test',
    'file_mdm',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an admin user.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // Set the image factory.
    $this->imageFactory = $this->container->get('image.factory');

    // Set the file system.
    $this->fileSystem = $this->container->get('file_system');

    // Prepare a directory for test file results.
    $this->testDirectory = 'public://imagetest';
  }

  /**
   * Helper to setup the image toolkit.
   *
   * @param string $binaries
   *   The graphics package binaries to use for testing.
   * @param bool $check_path
   *   Whether the path to binaries should be tested.
   */
  protected function setUpToolkit($binaries, $check_path = TRUE) {
    // Change the toolkit.
    \Drupal::configFactory()->getEditable('system.image')
      ->set('toolkit', 'imagemagick')
      ->save();

    // Execute tests with selected binaries.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('debug', TRUE)
      ->set('binaries', $binaries)
      ->set('quality', 100)
      ->save();

    if ($check_path) {
      // The test can only be executed if binaries are available on the shell
      // path.
      $status = \Drupal::service('image.toolkit.manager')->createInstance('imagemagick')->getExecManager()->checkPath('');
      if (!empty($status['errors'])) {
        // Bots running automated test on d.o. do not have binaries installed,
        // so the test will be skipped; it can be run locally where binaries
        // are installed.
        $this->markTestSkipped("Tests for '{$binaries}' cannot run because the binaries are not available on the shell path.");
      }
    }

    // Set the toolkit on the image factory.
    $this->imageFactory->setToolkitId('imagemagick');

    // Test that the image factory is set to use the Imagemagick toolkit.
    $this->assertEquals('imagemagick', $this->imageFactory->getToolkitId(), 'The image factory is set to use the \'imagemagick\' image toolkit.');

    // Prepare directory.
    file_unmanaged_delete_recursive($this->testDirectory);
    file_prepare_directory($this->testDirectory, FILE_CREATE_DIRECTORY);
  }

  /**
   * Test deprecation of module arguments alter hook.
   *
   * @group legacy
   *
   * @todo the expectedDeprecation annotation does not work if tests are
   *   skipped.
   * @see https://github.com/symfony/symfony/pull/25757
   *
   * #expectedDeprecation The deprecated alter hook hook_imagemagick_pre_parse_file_alter() is implemented in these functions: imagemagick_test_imagemagick_pre_parse_file_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH event. See https://www.drupal.org/project/imagemagick/issues/3043136.
   * #expectedDeprecation The deprecated alter hook hook_imagemagick_arguments_alter() is implemented in these functions: imagemagick_test_imagemagick_arguments_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE or a ImagemagickExecutionEvent::PRE_CONVERT_EXECUTE event. See https://www.drupal.org/project/imagemagick/issues/3043136.
   * #expectedDeprecation The deprecated alter hook hook_imagemagick_post_save_alter() is implemented in these functions: imagemagick_test_imagemagick_post_save_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::POST_SAVE event. See https://www.drupal.org/project/imagemagick/issues/3043136.
   */
  public function testAlterHooksDeprecation() {
    $this->setUpToolkit('imagemagick');
    $this->expectDeprecation('The deprecated alter hook hook_imagemagick_pre_parse_file_alter() is implemented in these functions: imagemagick_test_imagemagick_pre_parse_file_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH event. See https://www.drupal.org/project/imagemagick/issues/3043136.');
    $this->expectDeprecation('The deprecated alter hook hook_imagemagick_arguments_alter() is implemented in these functions: imagemagick_test_imagemagick_arguments_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE or a ImagemagickExecutionEvent::PRE_CONVERT_EXECUTE event. See https://www.drupal.org/project/imagemagick/issues/3043136.');
    $this->expectDeprecation('The deprecated alter hook hook_imagemagick_post_save_alter() is implemented in these functions: imagemagick_test_imagemagick_post_save_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::POST_SAVE event. See https://www.drupal.org/project/imagemagick/issues/3043136.');
    $this->getTestFiles('image');
    $image_uri = "public://image-test.png";
    $image = $this->imageFactory->get($image_uri);

    // Setup a list of arguments.
    $image->getToolkit()->arguments()
      ->add("-resize 100x75!")
      ->add("-quality 75");

    // Save the derived image.
    $image->save($image_uri . '.derived');
  }

  /**
   * Test deprecation of ImagemagickExecManager::getModuleHandler.
   *
   * @group legacy
   *
   * @todo the expectedDeprecation annotation does not work if tests are
   *   skipped.
   * @see https://github.com/symfony/symfony/pull/25757
   *
   * #expectedDeprecation The deprecated alter hook hook_imagemagick_pre_parse_file_alter() is implemented in these functions: imagemagick_test_imagemagick_pre_parse_file_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH event. See https://www.drupal.org/project/imagemagick/issues/3043136.
   * #expectedDeprecation The deprecated alter hook hook_imagemagick_arguments_alter() is implemented in these functions: imagemagick_test_imagemagick_arguments_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE or a ImagemagickExecutionEvent::PRE_CONVERT_EXECUTE event. See https://www.drupal.org/project/imagemagick/issues/3043136.
   * #expectedDeprecation Drupal\imagemagick\ImagemagickExecManager::getModuleHandler is deprecated in 8.x-2.5, will be removed in 8.x-3.0. No replacement suggested, Imagemagick hooks have been dropped in favour of event subscribers. See https://www.drupal.org/project/imagemagick/issues/3043136.
   */
  public function testGetModuleHandlerDeprecation() {
    $this->setUpToolkit('imagemagick');
    $this->expectDeprecation('The deprecated alter hook hook_imagemagick_pre_parse_file_alter() is implemented in these functions: imagemagick_test_imagemagick_pre_parse_file_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH event. See https://www.drupal.org/project/imagemagick/issues/3043136.');
    $this->expectDeprecation('The deprecated alter hook hook_imagemagick_arguments_alter() is implemented in these functions: imagemagick_test_imagemagick_arguments_alter. Deprecated in 8.x-2.5, will be removed in 8.x-3.0. Use an event subscriber to react on a ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE or a ImagemagickExecutionEvent::PRE_CONVERT_EXECUTE event. See https://www.drupal.org/project/imagemagick/issues/3043136.');
    $this->expectDeprecation('Drupal\imagemagick\ImagemagickExecManager::getModuleHandler is deprecated in 8.x-2.5, will be removed in 8.x-3.0. No replacement suggested, Imagemagick hooks have been dropped in favour of event subscribers. See https://www.drupal.org/project/imagemagick/issues/3043136.');
    $this->getTestFiles('image');
    $image = $this->imageFactory->get("public://image-test.png");
    $this->assertInstanceOf(ModuleHandlerInterface::class, $image->getToolkit()->getExecManager()->getModuleHandler());
  }

}
