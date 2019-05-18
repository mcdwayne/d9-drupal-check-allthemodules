<?php

namespace Drupal\Tests\protected_file\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides methods for testing Protected File module's field handling.
 */
abstract class ProtectedFileTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'file', 'protected_file', 'field_ui');

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $permissions = array(
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer users',
      'administer permissions',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'administer nodes',
      'bypass node access',
      'download protected file',
    );
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Creates a new file field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   *
   * @return FieldStorageConfig $field_storage
   *   The field_storage created.
   */
  public function createProtectedFileField($name, $entity_type, $bundle, $storage_settings = array(), $field_settings = array(), $widget_settings = array()) {
    $field_storage = FieldStorageConfig::create(array(
      'entity_type' => $entity_type,
      'field_name' => $name,
      'type' => 'protected_file',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ));
    $field_storage->save();

    $this->attachProtectedFileField($name, $entity_type, $bundle, $field_settings, $widget_settings);
    return $field_storage;
  }

  /**
   * Attaches a file field to an entity.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type this field will be added to.
   * @param string $bundle
   *   The bundle this field will be added to.
   * @param array $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  public function attachProtectedFileField($name, $entity_type, $bundle, $field_settings = array(), $widget_settings = array()) {
    $field = array(
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    );
    FieldConfig::create($field)->save();

    entity_get_form_display($entity_type, $bundle, 'default')
      ->setComponent($name, array(
        'type' => 'protected_file_widget',
        'settings' => $widget_settings,
      ))
      ->save();
    // Assign display settings.
    entity_get_display($entity_type, $bundle, 'default')
      ->setComponent($name, array(
        'label' => 'hidden',
        'type' => 'protected_file_formatter',
      ))
      ->save();
  }

  /**
   * Create a file and save it to the files table and assert that it occurs
   * correctly.
   *
   * @param string $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   * @return \Drupal\file\FileInterface
   *   File entity.
   */
  function createFile($filepath = NULL, $contents = NULL, $scheme = NULL) {
    // Don't count hook invocations caused by creating the file.
    \Drupal::state()->set('file_test.count_hook_invocations', FALSE);
    $file = File::create([
      'uri' => $this->createUri($filepath, $contents, $scheme),
      'uid' => 1,
    ]);
    $file->save();
    // Write the record directly rather than using the API so we don't invoke
    // the hooks.
    $this->assertTrue($file->id() > 0, 'The file was added to the database.');

    \Drupal::state()->set('file_test.count_hook_invocations', TRUE);
    return $file;
  }

  /**
   * Creates a file and returns its URI.
   *
   * @param string $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   *
   * @return string
   *   File URI.
   */
  function createUri($filepath = NULL, $contents = NULL, $scheme = NULL) {
    if (!isset($filepath)) {
      // Prefix with non-latin characters to ensure that all file-related
      // tests work with international filenames.
      $filepath = 'Файл для тестирования ' . $this->randomMachineName();
    }
    if (!isset($scheme)) {
      $scheme = file_default_scheme();
    }
    $filepath = $scheme . '://' . $filepath;

    if (!isset($contents)) {
      $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    }

    file_put_contents($filepath, $contents);
    $this->assertTrue(is_file($filepath), t('The test file exists on the disk.'), 'Create test file');
    return $filepath;
  }

  /**
   * Uploads a file to a node.
   *
   * @param \Drupal\file\FileInterface $file
   *   The File to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param int|string $nid_or_type
   *   A numeric node id to upload files to an existing node, or a string
   *   indicating the desired bundle for a new node.
   * @param bool $new_revision
   *   The revision number.
   * @param array $extras
   *   Additional values when a new node is created.
   *
   * @return int
   *   The node id.
   */
  public function uploadNodeFile(FileInterface $file, $field_name, $nid_or_type, $new_revision = TRUE, array $extras = array()) {
    return $this->uploadNodeFiles([$file], $field_name, $nid_or_type, $new_revision, $extras);
  }

  /**
   * Uploads multiple files to a node.
   *
   * @param \Drupal\file\FileInterface[] $files
   *   The files to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param int|string $nid_or_type
   *   A numeric node id to upload files to an existing node, or a string
   *   indicating the desired bundle for a new node.
   * @param bool $new_revision
   *   The revision number.
   * @param array $extras
   *   Additional values when a new node is created.
   *
   * @return int
   *   The node id.
   */
  function uploadNodeFiles(array $files, $field_name, $nid_or_type, $new_revision = TRUE, array $extras = array()) {
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'revision' => (string) (int) $new_revision,
    );

    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    if (is_numeric($nid_or_type)) {
      $nid = $nid_or_type;
      $node_storage->resetCache(array($nid));
      $node = $node_storage->load($nid);
    }
    else {
      // Add a new node.
      $extras['type'] = $nid_or_type;
      $node = $this->drupalCreateNode($extras);
      $nid = $node->id();
      // Save at least one revision to better simulate a real site.
      $node->setNewRevision();
      $node->save();
      $node_storage->resetCache(array($nid));
      $node = $node_storage->load($nid);
      $this->assertNotEqual($nid, $node->getRevisionId(), 'Node revision exists.');
    }

    // Attach files to the node.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    // File input name depends on number of files already uploaded.
    $field_num = count($node->{$field_name});
    $name = 'files[' . $field_name . "_$field_num]";
    if ($field_storage->getCardinality() != 1) {
      $name .= '[]';
    }

    foreach ($files as $file) {
      $file_path = $this->container->get('file_system')->realpath($file->getFileUri());
      if (count($files) == 1) {
        $edit[$name] = $file_path;
      }
      else {
        $edit[$name][] = $file_path;
      }
    }
    $this->drupalPostForm("node/$nid/edit", $edit, 'Save');

    return $nid;
  }

}
