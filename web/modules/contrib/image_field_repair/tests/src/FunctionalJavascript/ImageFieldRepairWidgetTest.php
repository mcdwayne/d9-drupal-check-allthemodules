<?php

namespace Drupal\Tests\image_field_repair\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the image field widget support multiple upload correctly.
 *
 * @group image_field_repair
 */
class ImageFieldRepairWidgetTest extends JavascriptTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'image',
    'image_field_repair',
  ];

  /**
   * Tests image widget element support multiple upload correctly.
   */
  public function testWidgetElementMultipleUploads() {
    /** @var \Drupal\Core\Image\ImageFactory $image_factory */
    $image_factory = \Drupal::service('image.factory');
    $file_system = \Drupal::service('file_system');

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $field_name = 'images';
    $storage_settings = ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED];
    $field_settings = ['alt_field_required' => 0];
    $this->createImageField($field_name, 'article', $storage_settings, $field_settings);
    $this->drupalLogin($this->drupalCreateUser(['access content', 'create article content']));
    $this->drupalGet('node/add/article');
    $this->xpath('//input[@name="title[0][value]"]')[0]->setValue('Test');

    $images = $this->getTestFiles('image');
    $images = array_slice($images, 0, 5);

    $paths = [];
    foreach ($images as $image) {
      $paths[] = $file_system->realpath($image->uri);
    }

    $remote_paths = [];
    foreach ($paths as $path) {
      $remote_paths[] = $this->uploadFileRemotePath($path);
    }

    $multiple_field = $this->xpath('//input[@multiple]')[0];
    $multiple_field->setValue(implode("\n", $remote_paths));
    $this->assertSession()->waitForElementVisible('css', '[data-drupal-selector="edit-images-4-preview"]');
    $this->getSession()->getPage()->findButton('Save')->click();

    $node = Node::load(1);
    foreach ($paths as $delta => $path) {
      $node_image = $node->{$field_name}[$delta];
      $original_image = $image_factory->get($path);
      $this->assertEquals($node_image->width, $original_image->getWidth(), "Correct width of image #$delta");
      $this->assertEquals($node_image->height, $original_image->getHeight(), "Correct height of image #$delta");
    }
  }

  /**
   * Uploads a file to the Selenium instance for get remote path.
   *
   * Copied from \Behat\Mink\Driver\Selenium2Driver::uploadFile().
   *
   * @param string $path
   *   The path to the file to upload.
   *
   * @return string
   *   The remote path.
   *
   * @todo: Remove after https://www.drupal.org/project/drupal/issues/2947517.
   */
  protected function uploadFileRemotePath($path) {
    $tempFilename = tempnam('', 'WebDriverZip');
    $archive = new \ZipArchive();
    $archive->open($tempFilename, \ZipArchive::CREATE);
    $archive->addFile($path, basename($path));
    $archive->close();
    $remotePath = $this->getSession()->getDriver()->getWebDriverSession()->file(['file' => base64_encode(file_get_contents($tempFilename))]);
    unlink($tempFilename);
    return $remotePath;
  }

}
