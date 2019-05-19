<?php

namespace Drupal\Tests\track_file_downloads\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Defines class for testing download tracking functionality.
 *
 * @group track_file_downloads
 */
class TrackFileDownloadsFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'track_file_downloads',
    'node',
  ];

  /**
   * The test file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // We need something the user will have access to to reference the file from
    // to get access to download it.
    // @see \Drupal\file\FileAccessControlHandler
    $this->drupalCreateContentType(['type' => 'page']);
    FieldStorageConfig::create([
      'type' => 'file',
      'entity_type' => 'node',
      'field_name' => 'file',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'file',
      'label' => 'File',
    ])->save();
    $this->user = $this->createUser(['access content']);
    $this->file = File::create([
      'uid' => $this->user->id(),
      'uri' => 'private://test.txt',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($this->file->getFileUri(), 'hello world');
    $this->file->save();
    $node = $this->drupalCreateNode(['status' => 1]);
    $node->set('file', $this->file)->save();

    $this->drupalLogin($this->user);
  }

  /**
   * Test that downloading a file increments the tracker count.
   */
  public function testTrackDownloadCount() {
    // Ensure the tracker entity was created.
    $entities = \Drupal::entityTypeManager()->getStorage('file_tracker')->loadByProperties(['file__target_id' => $this->file->id()]);
    $this->assertNotEmpty($entities);
    // We don't want to consider the tracker as a "usage" of the file.
    $usage = \Drupal::service('file.usage')->listUsage($this->file);
    $this->assertArrayNotHasKey('file_tracker', $usage['file']);
    $this->assertNotEmpty($usage['file']['node']);
    /** @var \Drupal\track_file_downloads\Entity\FileTrackerInterface $tracker */
    $tracker = reset($entities);
    $this->assertDownloadCount(0, $tracker->id());
    $url = Url::fromUri(file_create_url($tracker->getFile()->getFileUri()));
    $this->drupalGet($url);
    $this->assertDownloadCount(1, $tracker->id());
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), ['skip file tracking']);
    $this->drupalGet($url);
    $this->assertDownloadCount(1, $tracker->id());
  }

  /**
   * Assert the download count.
   *
   * @param int $count
   *   The expected count.
   * @param int $entity_id
   *   The entity id to test against.
   */
  protected function assertDownloadCount($count, $entity_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('file_tracker');
    $storage->resetCache([$entity_id]);
    /** @var \Drupal\track_file_downloads\Entity\FileTrackerInterface $tracker */
    $tracker = $storage->load($entity_id);
    $this->assertEquals($count, $tracker->getDownloadCount());
  }

}
