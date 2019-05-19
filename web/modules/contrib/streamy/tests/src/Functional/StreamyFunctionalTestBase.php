<?php

namespace Drupal\Tests\streamy\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Class StreamyFunctionalTestBase
 *
 * @package Drupal\streamy\Tests\Functional
 */
abstract class StreamyFunctionalTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'streamy', 'field', 'field_ui', 'node', 'file', 'image', 'user'];

  /**
   * @var
   */
  protected $entityType;

  /**
   * @var
   */
  protected $user;

  /**
   * Creates a date test field.
   */
  protected $fieldStorage;

  /**
   * @var
   */
  protected $field;

  /**
   * @var
   */
  protected $bundle;

  /**
   * @var
   */
  protected $fieldName;

  /**
   * @var
   */
  protected $display;

  /**
   * @var
   */
  protected $assetsDir;

  /**
   * @var
   */
  protected $publicFSfolder1;

  /**
   * @var
   */
  protected $publicFSfolder2;

  /**
   * @var
   */
  protected $privateFSfolder1;

  /**
   * @var
   */
  protected $privateFSfolder2;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Defining public and private folder names
    $this->publicFSfolder1 = 'testwebtest1';
    $this->publicFSfolder2 = 'testwebtest2';
    $this->privateFSfolder1 = 'testwebtestpvt1';
    $this->privateFSfolder2 = 'testwebtestpvt2';


    // Defining the local assets dir containig test images
    $this->assetsDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;

    // Creating a drupal admin user
    $this->user = $this->drupalCreateUser([
                                            'administer site configuration',
                                            'administer content types',
                                            'administer node fields',
                                            'administer display modes',
                                            'administer nodes',
                                            'administer node form display',
                                            'administer image styles',
                                          ]);
    $this->drupalLogin($this->user);

    $this->entityType = 'node';
    $this->bundle = 'article';

    // Creating content type Article
    NodeType::create(['type' => $this->bundle])->save();
    $this->createFieldImage();
  }

  /**
   * Creates a field image for an $entityType $bundle.
   */
  public function createFieldImage() {
    $bundle = $this->bundle;
    $this->fieldName = strtolower($this->randomMachineName());
    FieldStorageConfig::create([
                                 'entity_type' => $this->entityType,
                                 'field_name'  => $this->fieldName,
                                 'type'        => 'image',
                                 'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
                               ])->save();
    FieldConfig::create([
                          'entity_type' => $this->entityType,
                          'field_name'  => $this->fieldName,
                          'bundle'      => $bundle,
                          'settings'    => [
                            'file_extensions' => 'jpg',
                            'file_directory'  => '',
                          ],
                        ])->save();
    $form_display = EntityFormDisplay::create([
                                                'targetEntityType' => $this->entityType,
                                                'bundle'           => $this->bundle,
                                                'mode'             => 'default',
                                                'status'           => TRUE,
                                              ])->setComponent($this->fieldName, ['settings' => []]);
    $form_display->save();
    $form_display->setStatus(TRUE)->save();


    $this->display = entity_get_display($this->entityType, $bundle, 'default')
      ->setComponent($this->fieldName, ['type' => 'image', 'label' => 'hidden']);
    $this->display->save();
  }

  /**
   * @param string $dir
   *
   * @return mixed
   */
  protected function getRandomImgFromDir($dir = NULL) {
    if ($dir === NULL) {
      $dir = $this->assetsDir;
    }
    $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $images = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

    $randomImage = $images[array_rand($images)];
    return $randomImage;
  }

  /**
   * Gets public files Drupal folder with trailing slash.
   *
   * @param bool $absolute
   *    Returns a full absolute path from the root of your drive.
   * @return string
   */
  public function getPublicFilesDirectory($absolute = FALSE) {
    if ($absolute) {
      $public_folder = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      return rtrim($public_folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    $streamWrapperPublic = \Drupal::service('stream_wrapper.public');
    return rtrim($streamWrapperPublic->basePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
  }

  /**
   * Sets correct Streamy settings by using a relative dir
   * of the current browsertest avoiding creation of files and folders
   * in the main filesystem.
   */
  protected function setStreamyConfiguration() {
    $public_folder = $this->getPublicFilesDirectory();

    // Plugin Local
    $pluginConfig = [
      'streamy'    => [
        'master' => [
          'root' => $public_folder . $this->publicFSfolder1,
        ],
        'slave'  => [
          'root' => $public_folder . $this->publicFSfolder2,
        ],
      ],
      'streamypvt' => [
        'master' => [
          'root' => $public_folder . $this->privateFSfolder1,
        ],
        'slave'  => [
          'root' => $public_folder . $this->privateFSfolder2,
        ],
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.local');
    $config->set('plugin_configuration', $pluginConfig)
           ->save();

    // Main streamy configuration
    $schemes = [
      'streamy'    => [
        'master'      => 'local',
        'slave'       => 'local',
        'cdn_wrapper' => '',
        'enabled'     => '1',
      ],
      'streamypvt' => [
        'master'      => 'local',
        'slave'       => 'local',
        'cdn_wrapper' => '',
        'enabled'     => '1',
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('streamy.streamy');
    $config->set('plugin_configuration', $schemes)
           ->save();
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
    $this->drupalPostForm("/admin/structure/types/manage/{$bundle}/fields/{$field_id}/storage", $edit, $this->t('Save field settings'));
    $this->assertSession()->statusCodeEquals(200); // 'Storage set to scheme: ' . $storage_id
    $this->assertSession()->responseNotContains('The selected stream ' . $storage_id .
                                                ' is not properly configure and cannot be used in the current settings. Please visit the Streamy settings page to configure it.');
  }

  /**
   * Execute the Streamy Queue without running CRON.
   *
   * @throws \Exception
   */
  protected function executeStreamyQueue() {
    $queue_factory = \Drupal::service('queue');
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('streamy_fallback_queue_worker');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('streamy_fallback_queue_worker');

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      } catch (\Exception $e) {
        throw new \Exception('Failed to work the Streamy queue!');
      }
    }
  }

}
