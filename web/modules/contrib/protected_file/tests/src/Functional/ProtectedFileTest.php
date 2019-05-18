<?php

namespace Drupal\Tests\protected_file\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\file\Entity\File;

/**
 * Provides testing for Protected File module's field handling.
 *
 * @group protected_file
 */
class ProtectedFileTest extends ProtectedFileTestBase {

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
   * The viewDisplay entity.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $viewDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Provide tests for a protected file type.
   */
  public function testProtectedFile() {
    $this->drupalGet('admin/config/media/file-system');
    $fields = [
      'file_default_scheme' => 'private',
    ];
    // Check that public and private can be selected as default scheme.
    $this->assertText('Public local files served by the webserver.');
    $this->assertText('Private local files served by Drupal.');
    $this->drupalPostForm(NULL, $fields, 'Save configuration');
    $this->assertText('The configuration options have been saved.');

    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $storage_settings = [
      'cardinality' => -1,
      'display_field' => TRUE,
      'display_default' => TRUE,
      'uri_scheme' => 'private',
    ];
    $field_settings = [
      'description_field' => '1',
      'file_directory' => '',
    ];
    $this->createProtectedFileField($field_name, 'node', $type_name, $storage_settings, $field_settings);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $field_id = $field->id();

    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id/storage");
    $this->assertFieldByXpath('//input[@id="edit-settings-uri-scheme-public" and @disabled="disabled"]', 'public', 'Upload destination setting disabled.');

    $this->drupalGet("admin/structure/types/manage/$type_name/display");
    $this->assertFieldByName("fields[$field_name][type]", 'protected_file_formatter');
    $this->drupalGet("admin/structure/types/manage/$type_name/form-display");
    $this->assertFieldByName("fields[$field_name][type]", 'protected_file_widget');
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id");
    $this->assertFieldChecked('edit-settings-description-field');

    $contents = $this->randomMachineName(8);
    $contents_other = $this->randomMachineName(8);
    $file = $this->createFile('file_test_1.txt', $contents, 'private');
    $file->setPermanent();
    $file->save();

    $file_other = $this->createFile('file_test_2.txt', $contents_other, 'private');
    $file_other->setPermanent();
    $file_other->save();


    $nid = $this->uploadNodeFiles([$file], $field_name, $type_name);
    $this->drupalGet("/node/$nid");
    $this->assertText('file_test_1');

    $nid = $this->uploadNodeFiles([$file_other], $field_name, $nid);
    $this->drupalGet("/node/$nid");
    $this->assertText('file_test_2');

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache([$nid]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);
    $files_ids = $node->{$field_name}->getValue();

    $file = File::load($files_ids[0]['target_id']);
    $file_other = File::load($files_ids[1]['target_id']);

    // Add a description and make sure that it is displayed.
    // Protect the first file.
    $this->drupalGet("/node/$nid/edit");
    $description = 'file description';
    $description_other = 'file other description';
    $edit = array(
      $field_name . '[0][description]' => $description,
      $field_name . '[0][display]' => TRUE,
      $field_name . '[0][protected_file]' => TRUE,
      $field_name . '[1][description]' => $description_other,
      $field_name . '[1][display]' => TRUE,
    );
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save'));
    $this->assertText($description);
    $this->assertNoText($file->getFilename());
    $this->assertText($description_other);

    $this->drupalGet("/node/$nid");
    $this->assertSession()->linkExists($description);
    $this->clickLink($description);
    $this->assertResponse(200);

    $this->drupalGet("/node/$nid");
    $this->assertSession()->linkExists($description_other);
    $this->clickLink($description_other);
    $this->assertResponse(200);

    // Anonymous can not access to file protected.
    $this->drupalLogout();
    $this->drupalGet("/node/$nid");
    $this->assertSession()->linkByHrefExists('/user/login', 0);
    $this->clickLink($description);
    $this->assertText('Log in');
    $this->assertResponse(200);

    $url_file = file_create_url($file->getFileUri());
    $url_file_other = file_create_url($file_other->getFileUri());

    $this->drupalGet("/node/$nid");
    // Try to download the file directly.
    $this->drupalGet($url_file);
    $this->assertResponse(403);

    $this->drupalGet("/node/$nid");
    $this->clickLink($description_other);
    $this->assertResponse(200);

    // Try to download the file directly.
    $this->drupalGet($url_file_other);
    $this->assertResponse(200);

    $this->drupalLogin($this->adminUser);
    $settings = [
      'protected_file_new_window' => 1,
      'protected_file_path' => '/user/login',
      'redirect_to_file' => 1,
      'protected_file_modal' => 0,
      'protected_file_message' => 'You need to be logged in to be able to download this file',
    ];
    $this->setViewDisplay('node.' . $type_name . '.default', 'node', $type_name, $field_name, 'protected_file_formatter', $settings);
    $this->drupalGet("admin/structure/types/manage/$type_name/display");

    // Anonymous can not access to file protected.
    $this->drupalLogout();

    $this->drupalGet("/node/$nid");
    $file_uri = file_url_transform_relative(file_create_url($file->getFileUri()));
    $this->assertLinkByHref('/user/login?destination=' . $file_uri, 0);
    $this->assertLinkByHref('/user/login?destination', 0);
  }

  /**
   * Set the widget for a component in a View display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name to set.
   * @param string $formatter_id
   *   The formatter id to set.
   * @param array $settings
   *   The settings of widget.
   * @param string $mode
   *   The mode name.
   */
  protected function setViewDisplay($form_display_id, $entity_type, $bundle, $field_name, $formatter_id, $settings, $mode = 'default') {
    // Set article's view display.
    $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    if (!$this->viewDisplay) {
      EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    }
    if ($this->viewDisplay instanceof EntityViewDisplayInterface) {
      $this->viewDisplay->setComponent($field_name, [
        'type' => $formatter_id,
        'settings' => $settings,
      ])->save();
    }

  }

}
