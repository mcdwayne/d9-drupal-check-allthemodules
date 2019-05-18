<?php

namespace Drupal\pathed_file\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the PathedFile stuff.
 *
 * @group pathed_file
 */
class PathedFileUITest extends WebTestBase {
  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['pathed_file', 'path', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array(
      'administer pathed files',
      'administer url aliases',
      // Viewing a pathed file entity requires "access content" permission,
      // which is provided by the "node" module.
      'access content',
    ));
  }

  public function testPathedFileUI() {
    $random_number = rand();
    $base_path = 'admin/structure/pathed-files';
    $label = 'Pathed file test ' . $random_number;
    $internal_name = 'pathed_file_test_' . $random_number;
    $path = 'pathed-file-test-' . $random_number . '.html';
    $content = 'I am the page\'s content.';

    $this->drupalLogin($this->adminUser);
    $this->drupalGet($base_path);
    $this->assertRaw('Manage of all the pathed files in this list');

    // First, adds a pathed-file entity.
    $this->drupalPostForm($base_path . '/add', array(
      'label' => $label,
      'id' => $internal_name,
      'path' => $path,
      'content' => $content,
    ), t('Create pathed file'));

    $this->assertUrl($base_path);
    $this->assertText($label);
    $this->assertLinkByHref($path);

    $query = \Drupal::entityQuery('pathed_file')
      ->condition('path', $path);
    $result = $query->execute();
    $this->assertTrue(count($result) === 1, 'Exactly one pathed file found.');

    $ids = array_keys($result);
    $id = reset($ids);
    $storage = \Drupal::entityManager()->getStorage('pathed_file');
    $pathed_file = $storage->load($id);
    $this->assertIdentical($pathed_file->id(), $internal_name, 'ID matches');
    $this->assertIdentical($pathed_file->get('label'), $label, 'Label matches');
    $this->assertIdentical($pathed_file->get('path'), $path, 'Path matches');
    $this->assertIdentical($pathed_file->get('content'), $content, 'Content matches');

    $this->drupalGet($path);
    $this->assertRaw($content, 'Viewing pathed file shows correct content.');

    // Now, edits the file to ensure it updates.
    $this->drupalGet("$base_path/$internal_name");
    $this->assertFieldByName('label', $label, 'Edit form: Label matches.');
    $this->assertFieldByName('path', $path, 'Edit form: Path matches.');
    $this->assertFieldByName('content', $content, 'Edit form: Content matches.');

    $label_updated = $label . ' (mod 1)';
    $path_updated = 'pathed-file-test-' . $random_number . '-mod-1.html';
    $content_updated = 'I am the page\'s content (mod 1).';
    $this->drupalPostForm("$base_path/$internal_name", array(
      'label' => $label_updated,
      'path' => $path_updated,
      'content' => $content_updated,
    ), t('Update pathed file'));

    // Re-loads this entity to ensure it has been updated.
    $pathed_file_updated = $storage->load($id);
    $this->assertIdentical($pathed_file_updated->get('label'), $label_updated, 'Updated: Label matches');
    $this->assertIdentical($pathed_file_updated->get('path'), $path_updated, 'Updated: Path matches');
    $this->assertIdentical($pathed_file_updated->get('content'), $content_updated, 'Updated: Content matches');

    // Re-views this entity to ensure it has been updated.
    $this->drupalGet($path_updated);
    $this->assertRaw($content_updated, 'Updated: Viewing pathed file shows correct content.');

    // Verifies the content-length and content-type HTTP Headers.
    $headers = $this->drupalGetHeaders();
    $this->assertEqual(strlen($content_updated), $headers['content-length'], 'Content length matches.');
    preg_match('#\.(.*)$#', $path_updated, $matches);
    \Drupal::moduleHandler()->loadInclude('pathed_file', 'inc', 'includes/mime_types_map');
    $map = _pathed_files_get_mime_types();
    $expected_content_type = $map[$matches[1]];
    $this->assertTrue(strpos($headers['content-type'], $expected_content_type) !== FALSE, 'Content type matches.');

    // Next, deletes the pathed file entity.
    $this->drupalGet("$base_path/$internal_name/delete");
    $this->assertText('Are you sure you want to delete pathed_file ' . $label_updated . '?', 'Removal warning displays.');
    $this->drupalPostForm("$base_path/$internal_name/delete", array(
      'confirm' => '1',
    ), t('Delete pathed file'));
    $this->assertNull($storage->load($id), 'Deleted file has been removed.');
    $this->drupalGet($path_updated);
    $this->assertResponse('404', 'Deleted file can no longer be accessed.');
    // Ensures the path alias for this entity was also removed.
    $alias_service = \Drupal::service('path.alias_storage');
    $this->assertFalse($alias_service->aliasExists('/' . $path_updated, LanguageInterface::LANGCODE_NOT_SPECIFIED), 'Path alias has been removed.');

    // Tests form validators on entity's form.
    $this->drupalPostForm($base_path . '/add', array(
      'label' => $label,
      'id' => $internal_name,
      'path' => 'index.php',
      'content' => $content,
    ), t('Create pathed file'));
    $this->assertRaw(t('Another file at this path exists. Please choose another path.'));

    // Tries to add two files with the same path. The second try should result
    // in an error message.
    for ($i = 1; $i <= 2; $i++) {
      $this->drupalPostForm($base_path . '/add', array(
        'label' => $label,
        'id' => $internal_name . '_' . $i,
        'path' => $path,
        'content' => $content,
      ), t('Create pathed file'));
    }
    $this->assertRaw(t('Another alias at this path exists. Please choose another path.'));
  }
}
