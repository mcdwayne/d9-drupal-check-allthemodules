<?php

namespace Drupal\Tests\streamy\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;

/**
 * Tries to upload a file by using the Local plugin.
 *
 * @group streamy
 */
class LocalPluginTest extends StreamyFunctionalTestBase {

  /**
   * Using the scheme streamy://
   * tries to create a node with an image by checking
   * if the Streamy behavior works.
   *
   * Checks if the image is coming from the Master|Slave stream by setting slow_stream
   * in the plugin configuration.
   */
  public function testSchemeStramyAndLocalPluginOnAnImageField() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->setStreamyConfiguration();

    drupal_flush_all_caches();

    $field = FieldConfig::loadByName('node', $this->bundle, $this->fieldName);
    $field_id = $field->id();
    $scheme = 'streamy';

    // Change the field setting to make its files private, and upload a file.
    $this->setStorageOnField($scheme, $this->bundle, $field_id);

    // Go to the page file system
    // Check there is no error message from Streamy
    $this->drupalGet('/node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $imgPath = $this->getRandomImgFromDir();
    $edit = [
      "title[0][value]"                     => $this->randomMachineName(10),
      "files[" . $this->fieldName . "_0][]" => $imgPath,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      "title[0][value]"                     => $this->randomMachineName(10),
      "files[" . $this->fieldName . "_0][]" => "",
      "$this->fieldName[0][alt]"            => 'Alt text!',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $this->assertSession()->statusCodeEquals(200);

    preg_match('|node/(\d+)|', $this->getUrl(), $match);
    $nid = $match[1];

    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$this->fieldName}->target_id);
    self::assertTrue($node_file instanceof File);
    if ($node_file instanceof File) {
      $this->_assertFileExists($node_file, 'New file saved to disk on node creation.');
    }

    // Checking if the file is in both filesystems paths
    $path1 = $this->getPublicFilesDirectory() . $this->publicFSfolder1 . DIRECTORY_SEPARATOR . $node_file->getFilename();
    $path2 = $this->getPublicFilesDirectory() . $this->publicFSfolder2 . DIRECTORY_SEPARATOR . $node_file->getFilename();
    self::assertTrue(is_file($path1), t('File exists in %path', ['%path' => $path1]));
    self::assertTrue(is_file($path2), t('File exists in %path', ['%path' => $path2]));

    // Asserting that the image is served by the Master stream
    $this->assertSession()->responseContains('<img src="/' . $path1 . '"',
                                             t('Image src is correctly coming from the Master stream: %path', ['%path' => $path1]));

    // Now we try to visit the same node by setting the local stream as slow, this should serve our file from the slave stream
    // Plugin Local
    $pluginConfig = \Drupal::configFactory()->get('streamy.local')->get('plugin_configuration');
    $pluginConfig['streamy']['master']['slow_stream'] = TRUE;
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('<img src="/' . $path2 . '"',
                                             t('Image src is correctly coming from the Slave stream: %path', ['%path' => $path2]));

    // Plugin Local Test
    $pluginConfig = \Drupal::configFactory()->get('streamy.local')->get('plugin_configuration');
    $pluginConfig['streamy']['slave']['slow_stream'] = TRUE;
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    $publicPath = $this->getPublicFilesDirectory() . $node_file->getFilename();
    $this->assertSession()->responseContains('<img src="/' . $publicPath . '"',
                                             t('Image src is correctly coming from the public folder: %path', ['%path' => $publicPath]));

    // Testing the fallback by removing the file on the first stream we should expect to find the file again after the page view
    $pluginConfig = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    $pluginConfig['streamy']['disableFallbackCopy'] = TRUE;
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    // Deleting file
    if (is_file($path1)) {
      unlink($path1);
    }
    self::assertFalse(is_file($path1), t('File does not exist in %path', ['%path' => $path1]));

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    self::assertFalse(is_file($path1), t('File has not been re-created in %path', ['%path' => $path1]));

    // Now let's do the same with fallback switched on, we should expect the file to be recreated
    $pluginConfig = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    $pluginConfig['streamy']['disableFallbackCopy'] = FALSE;
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    self::assertFalse(is_file($path1), t('File does not exist in %path', ['%path' => $path1]));

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    // Running queue to make sure the file is copied back to Master
    $this->executeStreamyQueue();

    self::assertTrue(is_file($path1), t('File has been re-created in %path', ['%path' => $path1]));

    // TODO: image style is returning 404, should return 200
    //    $this->drupalGet('/admin/config/media/image-styles');
    //    $this->drupalGet('/streamy/files/styles/thumbnail?file=test1.jpg&scheme=streamy&itok=VizoUail');
    //    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Using the scheme streamypvt://
   * tries to create a node with an image by checking
   * if the Streamy behavior works.
   *
   * Checks if the image is coming from the Master|Slave stream by setting slow_stream
   * in the plugin configuration.
   */
  public function testSchemeStramypvtAndLocalPluginOnAnImageField() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->setStreamyConfiguration();

    drupal_flush_all_caches();

    $field = FieldConfig::loadByName('node', $this->bundle, $this->fieldName);
    $field_id = $field->id();
    $scheme = 'streamypvt';

    // Change the field setting to make its files private, and upload a file.
    $this->setStorageOnField($scheme, $this->bundle, $field_id);

    // Go to the page file system
    // Check there is no error message from Streamy
    $this->drupalGet('/node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $imgPath = $this->getRandomImgFromDir();
    $edit = [
      "title[0][value]"                     => $this->randomMachineName(10),
      "files[" . $this->fieldName . "_0][]" => $imgPath,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      "title[0][value]"                     => $this->randomMachineName(10),
      "files[" . $this->fieldName . "_0][]" => "",
      "$this->fieldName[0][alt]"            => 'Alt text!',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $this->assertSession()->statusCodeEquals(200);

    preg_match('|node/(\d+)|', $this->getUrl(), $match);
    $nid = $match[1];

    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$this->fieldName}->target_id);
    self::assertTrue($node_file instanceof File);
    if ($node_file instanceof File) {
      $this->_assertFileExists($node_file, 'New file saved to disk on node creation.');
    }

    // Checking if the file is in both filesystems paths
    $path1 = $this->getPublicFilesDirectory() . $this->privateFSfolder1 . DIRECTORY_SEPARATOR . $node_file->getFilename();
    $path2 = $this->getPublicFilesDirectory() . $this->privateFSfolder2 . DIRECTORY_SEPARATOR . $node_file->getFilename();
    $privatePath = '/_streamy/' . $scheme . '/' . $node_file->getFilename();

    self::assertTrue(is_file($path1), t('File exists in %path', ['%path' => $path1]));
    self::assertTrue(is_file($path2), t('File exists in %path', ['%path' => $path2]));

    // Asserting that the image is served by the private path
    $this->assertSession()->responseContains('<img src="' . $privatePath . '"',
                                             t('Image src is coming from the private path: %path', ['%path' => $privatePath]));

    // Now we try to visit the same node by setting the local stream as slow, this should serve our file from the slave stream
    // Plugin Local
    $pluginConfig = \Drupal::configFactory()->get('streamy.local')->get('plugin_configuration');
    $pluginConfig['streamypvt']['master']['slow_stream'] = TRUE;
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('<img src="' . $privatePath . '"',
                                             t('Image src is coming from the private path: %path', ['%path' => $privatePath]));

    // Plugin Local Test
    $pluginConfig = \Drupal::configFactory()->get('streamy.local')->get('plugin_configuration');
    $pluginConfig['streamy']['slave']['slow_stream'] = TRUE;
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('<img src="' . $privatePath . '"',
                                             t('Image src is coming from the private path: %path', ['%path' => $privatePath]));

    // Testing the fallback by removing the file on the first stream we should expect to find the file again after the page view
    $pluginConfig = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    $pluginConfig['streamypvt']['disableFallbackCopy'] = TRUE;
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    // Deleting file
    if (is_file($path1)) {
      unlink($path1);
    }
    self::assertFalse(is_file($path1), t('File does not exist in %path', ['%path' => $path1]));

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    self::assertFalse(is_file($path1), t('File has not been re-created in %path', ['%path' => $path1]));

    // Now let's do the same with fallback switched on, we should expect the file to be recreated
    $pluginConfig = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    $pluginConfig['streamypvt']['disableFallbackCopy'] = FALSE;
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    drupal_flush_all_caches();

    self::assertFalse(is_file($path1), t('File does not exist in %path', ['%path' => $path1]));

    $this->drupalGet('/node/' . $nid);
    $this->assertSession()->statusCodeEquals(200);

    // Running queue to make sure the file is copied back to Master
    $this->executeStreamyQueue();

    self::assertTrue(is_file($path1), t('File has been re-created in %path', ['%path' => $path1]));
  }

  /**
   * Asserts that a file exists physically on disk.
   */
  protected function _assertFileExists($file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file exists on the disk.', ['%file' => $file->getFileUri()]);
    self::assertTrue(is_file($file->getFileUri()), $message);
  }

}
