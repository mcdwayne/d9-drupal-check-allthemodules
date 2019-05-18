<?php

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\imagemagick\Kernel\ToolkitSetupTrait;

/**
 * Tests for ImagemagickEventSubscriber.
 *
 * @group Imagemagick
 */
class EventSubscriberTest extends BrowserTestBase {

  use ToolkitSetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'imagemagick', 'file_mdm'];

  /**
   * Test module's event subscriber.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testEventSubscriber($toolkit_id, $toolkit_config, array $toolkit_settings) {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    $fmdm = \Drupal::service('file_metadata_manager');

    // Change the Advanced Colorspace setting, must be included in the command
    // line.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('advanced.colorspace', 'GRAY')
      ->save();

    $image_uri = "public://image-test.png";
    $image = $this->imageFactory->get($image_uri);
    if (!$image->isValid()) {
      $this->fail("Could not load image $image_uri.");
    }

    // Check the source colorspace.
    if ($toolkit_settings['binaries'] === 'imagemagick') {
      $this->assertSame('SRGB', $image->getToolkit()->getColorspace());
    }
    else {
      $this->assertNull($image->getToolkit()->getColorspace());
    }

    // Setup a list of arguments.
    $image->getToolkit()->arguments()
      ->add("-resize 100x75!")
      ->add("-quality 75");

    // Save the derived image.
    $image->save($image_uri . '.derived');

    // Check expected command line.
    if (substr(PHP_OS, 0, 3) === 'WIN') {
      $expected = "-resize 100x75! -quality 75 -colorspace \"GRAY\"";
    }
    else {
      $expected = "-resize 100x75! -quality 75 -colorspace 'GRAY'";
    }
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));

    // Check that the colorspace has been actually changed in the file.
    Cache::InvalidateTags([
      'config:imagemagick.file_metadata_plugin.imagemagick_identify',
    ]);
    $fmdm->release($image_uri . '.derived');
    $image_md = $fmdm->uri($image_uri . '.derived');
    $image = $this->imageFactory->get($image_uri . '.derived');
    $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
    if ($toolkit_settings['binaries'] === 'imagemagick') {
      $this->assertSame('GRAY', $image->getToolkit()->getColorspace());
    }
    else {
      $this->assertNull($image->getToolkit()->getColorspace());
    }

    // Change the Prepend settings, must be included in the command line.
    // Once before the source image.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('prepend', '-debug All')
      ->set('prepend_pre_source', TRUE)
      ->save();
    $image = $this->imageFactory->get($image_uri);
    $image->getToolkit()->arguments()
      ->add("-resize 100x75!")
      ->add("-quality 75");
    $image->save($image_uri . '.derived');
    if (substr(PHP_OS, 0, 3) === 'WIN') {
      $expected = "-resize 100x75! -quality 75 -colorspace \"GRAY\"";
    }
    else {
      $expected = "-resize 100x75! -quality 75 -colorspace 'GRAY'";
    }
    $this->assertSame('-debug All', $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::PRE_SOURCE));
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));
    // Then after the source image.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('prepend_pre_source', FALSE)
      ->save();
    $image = $this->imageFactory->get($image_uri);
    $image->getToolkit()->arguments()
      ->add("-resize 100x75!")
      ->add("-quality 75");
    $image->save($image_uri . '.derived');
    if (substr(PHP_OS, 0, 3) === 'WIN') {
      $expected = "-debug All -resize 100x75! -quality 75 -colorspace \"GRAY\"";
    }
    else {
      $expected = "-debug All -resize 100x75! -quality 75 -colorspace 'GRAY'";
    }
    $this->assertSame('', $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::PRE_SOURCE));
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));
  }

}
