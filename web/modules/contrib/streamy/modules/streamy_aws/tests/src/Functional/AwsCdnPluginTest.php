<?php

namespace Drupal\Tests\streamy_aws\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\streamy\Functional\StreamyFunctionalTestBase;

/**
 * Tests the CDN plugin behaviour.
 *
 * @group streamy_aws
 */
class AwsCdnPluginTest extends StreamyFunctionalTestBase {

  public static $modules = ['system', 'streamy', 'field', 'field_ui', 'node', 'file', 'image', 'user', 'streamy_aws'];

  /**
   * @inheritdoc
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Checks if the image is coming from the CDN in a public and private stream.
   */
  public function testCDNbehaviour() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->setStreamyConfiguration();

    drupal_flush_all_caches();

    $field = FieldConfig::loadByName('node', $this->bundle, $this->fieldName);
    $field_id = $field->id();
    $scheme = 'streamy';

    // Configuring the node field to use streamy as default storage
    $this->setStorageOnField($scheme, $this->bundle, $field_id);

    $awsCdnConfig = \Drupal::configFactory()->get('streamy_aws.awscdn')->get('plugin_configuration');
    self::assertTrue($awsCdnConfig[$scheme]['enabled'], 'streamy cdn is enabled');

    $streamyConfig = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    self::assertTrue($streamyConfig[$scheme]['cdn_wrapper'] === 'awscdn', 'streamy cdn is set as cdn stream');

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

    // Asserting that the image is served by the Master stream
    $path1 = 'http://www.testlink.it/' . $node_file->getFilename();
    $this->assertSession()->responseContains('<img src="' . $path1 . '"'); // Image src is correctly coming from a CDN path

    // Now let's try to set a CDN on a private stream, we should not get the file from the CDN to pass the test

    $field = FieldConfig::loadByName('node', $this->bundle, $this->fieldName);
    $field_id = $field->id();
    $scheme = 'streamypvt';

    // Configuring the node field to use streamy as default storage
    $this->setStorageOnField($scheme, $this->bundle, $field_id);

    drupal_flush_all_caches();

    $awsCdnConfig = \Drupal::configFactory()->get('streamy_aws.awscdn')->get('plugin_configuration');
    self::assertTrue($awsCdnConfig[$scheme]['enabled'], 'streamy cdn is enabled');

    $streamyConfig = \Drupal::configFactory()->get('streamy.streamy')->get('plugin_configuration');
    self::assertTrue($streamyConfig[$scheme]['cdn_wrapper'] === 'awscdn', 'streamy cdn is set as cdn stream');

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

    // Asserting that the image is served by the Master stream
    $path1 = 'http://www.testlink.it/' . $node_file->getFilename();
    $this->assertSession()->responseNotContains('<img src="' . $path1 . '"'); // Image src is not coming from a CDN

    $pathPvt = '/_streamy/' . $scheme . '/' . $node_file->getFilename();
    $this->assertSession()->responseContains('<img src="' . $pathPvt . '"'); // Image src is coming from a private URL
  }

  /**
   * Asserts that a file exists physically on disk.
   */
  protected function _assertFileExists($file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file exists on the disk.', ['%file' => $file->getFileUri()]);
    self::assertTrue(is_file($file->getFileUri()), $message);
  }

  /**
   * Sets the storage on a given field for a given content type.
   *
   * @param $storage_id
   * @param $bundle
   * @param $field_id
   */
  public function setStorageOnField($storage_id, $bundle, $field_id) {
    // Change the field setting to make its files private, and upload a file.
    $edit = ['settings[uri_scheme]' => $storage_id];
    $this->drupalPostForm("/admin/structure/types/manage/$bundle/fields/$field_id/storage", $edit, t('Save field settings'));
    $this->assertSession()->statusCodeEquals(200, 'Storage set to scheme: ' . $storage_id);
  }

  /**
   * Sets correct Streamy settings by using a relative dir
   * of the current browsertest avoiding creation of files and folders
   * in the main filesystem.
   */
  protected function setStreamyConfiguration() {
    $public_folder = $this->getPublicFilesDirectory();

    // Plugin Config
    $pluginConfig = [
      'streamy'    => [
        'master' => [
          'root' => $public_folder . $this->publicFSfolder1,
        ],
        'slave' => [
          'root' => $public_folder . $this->publicFSfolder2,
        ],
      ],
      'streamypvt' => [
        'master' => [
          'root' => $public_folder . $this->privateFSfolder1,
        ],
        'slave' => [
          'root' => $public_folder . $this->privateFSfolder2,
        ],
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    // Awscdn plugin
    $pluginConfig = [
      'streamy'    => [
        'enabled' => TRUE,
        'url'     => 'www.testlink.it',
        'https'   => TRUE,
      ],
      'streamypvt' => [
        'enabled' => TRUE,
        'url'     => 'www.testlink2.it',
        'https'   => TRUE,
      ],
    ];

    // Saving the configuration
    $config = \Drupal::configFactory()->getEditable('streamy_aws.awscdn');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    // Main streamy configuration
    $schemes = [
      'streamy'    => [
        'master'      => 'local',
        'slave'       => 'local',
        'cdn_wrapper' => 'awscdn',
        'enabled'     => '1',
      ],
      'streamypvt' => [
        'master'      => 'local',
        'slave'       => 'local',
        'cdn_wrapper' => 'awscdn',
        'enabled'     => '1',
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $schemes)
           ->save();
  }

}
