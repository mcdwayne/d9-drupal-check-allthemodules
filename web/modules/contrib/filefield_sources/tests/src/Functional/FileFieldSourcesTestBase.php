<?php

namespace Drupal\Tests\filefield_sources\Functional;

use Drupal\Core\File\FileSystem;
use Drupal\Tests\file\Functional\FileFieldTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\Role;

/**
 * Base class for File Field Sources test cases.
 */
abstract class FileFieldSourcesTestBase extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filefield_sources'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected $typeName = 'article';

  protected $fieldName;

  protected $node;

  /**
   * Sets up for file field sources test cases.
   */
  protected function setUp() {
    parent::setUp();

    // Grant "administer node form display" permission.
    $roles = $this->adminUser->getRoles(TRUE);
    $rid = array_pop($roles);
    $role = Role::load($rid);
    $this->grantPermissions($role, ['administer node form display']);

    // Add node.
    $this->node = $this->drupalCreateNode();
    // Add file field.
    $this->fieldName = strtolower($this->randomMachineName());
    $this->createFileField($this->fieldName, 'node', $this->typeName);
  }

  /**
   * Sets up for imce test cases.
   */
  protected function setUpImce() {
    foreach ($this->adminUser->getRoles(TRUE) as $rid) {
      // Grant permission.
      $role = Role::load($rid);
      $this->grantPermissions($role, ['administer imce']);
      // Assign member profile to user's role.
      $edit["roles_profiles[$rid][public]"] = 'member';
      $this->drupalPostForm('admin/config/media/imce', $edit, t('Save configuration'));
    }
  }

  /**
   * Enable file field sources.
   *
   * @param array $sources
   *   List of sources to enable or disable. e.g
   *   array(
   *     'upload' => FALSE,
   *     'remote' => TRUE,
   *   ).
   */
  public function enableSources($sources = []) {
    $sources += ['upload' => TRUE];
    $map = [
      'upload' => 'Upload',
      'remote' => 'Remote URL',
      'clipboard' => 'Clipboard',
      'reference' => 'Reference existing',
      'attach' => 'File attach',
      'imce' => 'File browser',
    ];
    $sources = array_intersect_key($sources, $map);
    ksort($sources);

    // Upload source enabled by default.
    $manage_display = 'admin/structure/types/manage/' . $this->typeName . '/form-display';
    $this->drupalGet($manage_display);
    $this->assertSession()->responseContains("File field sources: upload");

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostForm(NULL, [], $this->fieldName . "_settings_edit");

    // Enable sources.
    $prefix = 'fields[' . $this->fieldName . '][settings_edit_form][third_party_settings][filefield_sources][filefield_sources][sources]';
    $edit = [];
    foreach ($sources as $source => $enabled) {
      $edit[$prefix . '[' . $source . ']'] = $enabled ? TRUE : FALSE;
    }
    $this->drupalPostForm(NULL, $edit, $this->fieldName . '_plugin_settings_update');
    $this->assertSession()->responseContains("File field sources: " . implode(', ', array_keys($sources)));

    // Save the form to save the third party settings.
    $this->drupalPostForm(NULL, [], t('Save'));

    $add_node = 'node/add/' . $this->typeName;
    $this->drupalGet($add_node);
    if (count($sources) > 1) {
      // We can switch between sources.
      foreach ($sources as $source => $enabled) {
        $label = $map[$source];
        $this->assertSession()->linkExists($label);
      }
    }
    else {
      foreach ($map as $source => $label) {
        $this->assertSession()->linkNotExists($label);
      }
    }
  }

  /**
   * Create permanent file entity.
   *
   * @return object
   *   Permanent file entity.
   */
  public function createPermanentFileEntity() {
    $file = $this->createTemporaryFileEntity();
    // Only permanent file can be referred.
    $file->status = FILE_STATUS_PERMANENT;
    // Author has permission to access file.
    $file->uid = $this->adminUser->id();
    $file->save();

    // Permanent file must be used by an entity.
    \Drupal::service('file.usage')
      ->add($file, 'file', 'node', $this->node->id());

    return $file;
  }

  /**
   * Create temporary file entity.
   *
   * @return object
   *   Temporary file entity.
   */
  public function createTemporaryFileEntity() {
    $file = $this->createTemporaryFile();

    // Add a filesize property to files as would be read by file_load().
    $file->filesize = filesize($file->uri);

    return entity_create('file', (array) $file);
  }

  /**
   * Create temporary file.
   *
   * @return object
   *   Permanent file object.
   */
  public function createTemporaryFile($path = '') {
    $filename = $this->randomMachineName() . '.txt';
    if (empty($path)) {
      $path = file_default_scheme() . '://';
    }
    $uri = $path . $filename;
    $contents = $this->randomString();

    // Change mode so that we can create files.
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    \Drupal::getContainer()->get('file_system')->chmod($path, FileSystem::CHMOD_DIRECTORY);

    file_put_contents($uri, $contents);
    $this->assertTrue(is_file($uri), 'The temporary file has been created.');

    // Change mode so that we can delete created file.
    \Drupal::getContainer()->get('file_system')->chmod($uri, FileSystem::CHMOD_FILE);

    // Return object similar to file_scan_directory().
    $file = new \stdClass();
    $file->uri = $uri;
    $file->filename = $filename;
    $file->name = pathinfo($filename, PATHINFO_FILENAME);
    return $file;
  }

  /**
   * Update file field sources settings.
   *
   * @param string $source_key
   *   Wrapper, defined by each source.
   * @param string $key
   *   Key, defined by each source.
   * @param mixed $value
   *   Value to set.
   */
  public function updateFilefieldSourcesSettings($source_key, $key, $value) {
    $manage_display = 'admin/structure/types/manage/' . $this->typeName . '/form-display';
    $this->drupalGet($manage_display);

    // Click on the widget settings button to open the widget settings form.
    $this->drupalPostForm(NULL, [], $this->fieldName . "_settings_edit");

    // Update settings.
    $name = 'fields[' . $this->fieldName . '][settings_edit_form][third_party_settings][filefield_sources][filefield_sources]' . "[$source_key][$key]";
    $edit = [$name => $value];
    $this->drupalPostForm(NULL, $edit, $this->fieldName . '_plugin_settings_update');

    // Save the form to save the third party settings.
    $this->drupalPostForm(NULL, [], t('Save'));
  }

  /**
   * Upload file by 'Attach' source.
   *
   * @param string $uri
   *   File uri.
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function uploadFileByAttachSource($uri = '', $filename = '', $delta = 0) {
    if ($uri) {
      $edit = [
        $this->fieldName . '[' . $delta . '][filefield_attach][filename]' => $uri,
      ];
    }
    else {
      $edit = [];
    }
    $this->drupalPostForm(NULL, $edit, $this->fieldName . '_' . $delta . '_attach');

    if ($filename) {
      $this->assertFileUploaded($filename, $delta);
    }
    else {
      $this->assertFileNotUploaded($delta);
    }
  }

  /**
   * Check to see if file is uploaded.
   *
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function assertFileUploaded($filename, $delta = 0) {
    $this->assertSession()->linkExists($filename);
    $xpath = '//input[@name="' . $this->fieldName . '_' . $delta . '_remove_button"]';
    $fields = $this->xpath($xpath);
    foreach ($fields as $field) {
      $this->assertTrue($field->getAttribute('value') == t('Remove'), 'After uploading a file, "Remove" button is displayed.');
    }
  }

  /**
   * Check to see if file is not uploaded.
   *
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function assertFileNotUploaded($delta = 0) {
    $xpath = '//input[@name="' . $this->fieldName . '_' . $delta . '_remove_button"]';
    $fields = $this->xpath($xpath);
    $this->assertTrue(empty($fields), '"Remove" button is not displayed.');
  }

  /**
   * Upload file by 'Reference' source.
   *
   * @param int $fid
   *   File id.
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function uploadFileByReferenceSource($fid = 0, $filename = '', $delta = 0) {
    $name = $this->fieldName . '[' . $delta . '][filefield_reference][autocomplete]';
    $value = $fid ? $filename . ' [fid:' . $fid . ']' : '';
    $edit = [$name => $value];
    $this->drupalPostForm(NULL, $edit, $this->fieldName . '_' . $delta . '_autocomplete_select');

    if ($filename) {
      $this->assertFileUploaded($filename, $delta);
    }
    else {
      $this->assertFileNotUploaded($delta);
    }
  }

  /**
   * Upload file by 'Clipboard' source.
   *
   * @param string $uri
   *   File uri.
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function uploadFileByClipboardSource($uri = '', $filename = '', $delta = 0) {
    $prefix = $this->fieldName . '[' . $delta . '][filefield_clipboard]';
    $file_content = $uri ? 'data:text/plain;base64,' . base64_encode(file_get_contents($uri)) : '';

    // Can't be used drupalPostForm here because the fields are of type hidden
    // and drupalPostForm don't see those, let's set the values manually.
    $this->drupalGet('node/add/article');
    $this->getSession()->getPage()->find('css', 'input[name="' . $prefix . '[filename]"]')->setValue($filename);
    $this->getSession()->getPage()->find('css', 'input[name="' . $prefix . '[contents]"]')->setValue($file_content);
    $this->getSession()->getPage()->pressButton($this->fieldName . '_' . $delta . '_clipboard_upload_button');

    if ($filename) {
      $this->assertFileUploaded($filename, $delta);
    }
    else {
      $this->assertFileNotUploaded($delta);
    }
  }

  /**
   * Upload file by 'Imce' source.
   *
   * @param string $uri
   *   File uri.
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function uploadFileByImceSource($uri = '', $filename = '', $delta = 0) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);
    $imce_path = str_replace("$scheme://", '', $uri);

    // Can't be used drupalPostForm here because the field type is hidden
    // and drupalPostForm don't see those, let's set the values manually.
    $this->drupalGet('node/add/article');
    $field_name = $this->fieldName . '[' . $delta . '][filefield_imce][imce_paths]';
    $this->getSession()->getPage()->find('css', 'input[name="' . $field_name . '"]')->setValue($imce_path);
    $this->getSession()->getPage()->pressButton($this->fieldName . '_' . $delta . '_imce_select');

    if ($filename) {
      $this->assertFileUploaded($filename, $delta);
    }
    else {
      $this->assertFileNotUploaded($delta);
    }
  }

  /**
   * Upload file by 'Remote' source.
   *
   * @param string $url
   *   File url.
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function uploadFileByRemoteSource($url = '', $filename = '', $delta = 0) {
    $name = $this->fieldName . '[' . $delta . '][filefield_remote][url]';
    $edit = [$name => $url];
    $this->drupalPostForm(NULL, $edit, $this->fieldName . '_' . $delta . '_transfer');

    if ($filename) {
      $this->assertFileUploaded($filename, $delta);
    }
    else {
      $this->assertFileNotUploaded($delta);
    }
  }

  /**
   * Upload file by 'Upload' source.
   *
   * @param string $uri
   *   File uri.
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function uploadFileByUploadSource($uri = '', $filename = '', $delta = 0, $multiple = FALSE) {
    $name = 'files[' . $this->fieldName . '_' . $delta . ']';
    if ($multiple) {
      $name .= '[]';
    }
    $edit = [
      $name => $uri ? \Drupal::getContainer()->get('file_system')->realPath($uri) : '',
    ];
    $this->drupalPostForm(NULL, $edit, $this->fieldName . '_' . $delta . '_upload_button');

    if ($filename) {
      $this->assertFileUploaded($filename, $delta);
    }
    else {
      $this->assertFileNotUploaded($delta);
    }
  }

  /**
   * Remove uploaded file.
   *
   * @param string $filename
   *   File name.
   * @param int $delta
   *   Delta in multiple values field.
   */
  public function removeFile($filename, $delta = 0) {
    $this->drupalPostForm(NULL, [], $this->fieldName . '_' . $delta . '_remove_button');

    // Ensure file is removed.
    $this->assertFileRemoved($filename);
  }

  /**
   * Check to see if file is removed.
   *
   * @param string $filename
   *   File name.
   */
  public function assertFileRemoved($filename) {
    $this->assertSession()->linkNotExists($filename);
  }

  /**
   * Get field setting.
   *
   * @param string $setting_name
   *   Setting name.
   */
  public function getFieldSetting($setting_name) {
    $field_definition = FieldConfig::load("node.{$this->typeName}.{$this->fieldName}");
    return $field_definition->getSetting($setting_name);
  }

}
